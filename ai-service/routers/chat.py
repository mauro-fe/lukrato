import re
from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from typing import Optional
import httpx

from config import settings

router = APIRouter()

_THINK_RE = re.compile(r"<think>.*?</think>", re.DOTALL)


class ChatRequest(BaseModel):
    message: str
    context: Optional[dict] = {}
    system_prompt: Optional[str] = None
    provider: Optional[str] = None


class ChatResponse(BaseModel):
    response: str
    provider: str
    tokens_prompt: Optional[int] = None
    tokens_completion: Optional[int] = None


def _build_fallback_prompt(context: dict) -> str:
    """Prompt simplificado caso o PHP não envie o system_prompt pronto."""
    base = (
        "Você é o assistente de IA do Lukrato, uma plataforma de gestão financeira. "
        "Responda sempre em português brasileiro, com dados precisos do contexto fornecido. "
        "Nunca invente dados. Se um dado não está no contexto, diga explicitamente."
    )
    if context:
        base += "\n\nDados do Sistema:\n"
        base += _format_context(context)
    return base


def _format_context(ctx: dict, indent: int = 0) -> str:
    """Formata o dicionário de contexto de forma legível para o LLM."""
    lines = []
    prefix = "  " * indent
    for key, value in ctx.items():
        label = key.replace("_", " ").title()
        if isinstance(value, dict):
            lines.append(f"{prefix}{label}:")
            lines.append(_format_context(value, indent + 1))
        elif isinstance(value, list):
            lines.append(f"{prefix}{label}:")
            for item in value:
                if isinstance(item, dict):
                    parts = [f"{k}: {v}" for k, v in item.items()]
                    lines.append(f"{prefix}  - {', '.join(parts)}")
                else:
                    lines.append(f"{prefix}  - {item}")
        else:
            lines.append(f"{prefix}{label}: {value}")
    return "\n".join(lines)


@router.post("", response_model=ChatResponse)
async def chat(req: ChatRequest):
    provider = req.provider or settings.ai_provider

    if provider != "ollama":
        raise HTTPException(
            status_code=400,
            detail=f"Provider '{provider}' não suportado. Use o endpoint PHP para OpenAI.",
        )

    system_prompt = req.system_prompt or _build_fallback_prompt(req.context)

    try:
        async with httpx.AsyncClient(timeout=120.0) as client:
            resp = await client.post(
                f"{settings.ollama_base_url}/api/chat",
                json={
                    "model": settings.ollama_model,
                    "messages": [
                        {"role": "system", "content": system_prompt},
                        {"role": "user", "content": req.message},
                    ],
                    "stream": False,
                    "think": False,
                    "options": {
                        "temperature": 0.7,
                        "num_predict": 1500,
                        "num_ctx": 8192,
                    },
                },
            )
            resp.raise_for_status()
            data = resp.json()

            content = data["message"]["content"]
            content = _THINK_RE.sub("", content).strip()

            return ChatResponse(
                response=content,
                provider="ollama",
                tokens_prompt=data.get("prompt_eval_count"),
                tokens_completion=data.get("eval_count"),
            )
    except httpx.ConnectError:
        raise HTTPException(status_code=503, detail="Ollama não está rodando em localhost:11434")
    except Exception as e:
        raise HTTPException(status_code=502, detail=f"Erro Ollama: {str(e)}")
