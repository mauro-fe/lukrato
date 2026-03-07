from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from typing import Optional
import openai
import httpx

from config import settings

router = APIRouter()


class ChatRequest(BaseModel):
    message: str
    context: Optional[dict] = {}
    provider: Optional[str] = None  # override do .env se necessário


class ChatResponse(BaseModel):
    response: str
    provider: str


def build_system_prompt(context: dict) -> str:
    base = (
        "Você é um assistente financeiro especializado integrado ao Lukrato, "
        "um sistema de gestão financeira pessoal. Você ajuda o administrador a "
        "entender os dados do sistema, analisar padrões financeiros e tomar "
        "decisões estratégicas. Responda sempre em português brasileiro de forma "
        "clara, objetiva e prática."
    )
    if context:
        base += f"\n\nContexto atual do sistema: {context}"
    return base


@router.post("", response_model=ChatResponse)
async def chat(req: ChatRequest):
    provider = req.provider or settings.ai_provider

    if provider == "openai":
        if not settings.openai_api_key:
            raise HTTPException(status_code=503, detail="OPENAI_API_KEY não configurada")

        client = openai.AsyncOpenAI(api_key=settings.openai_api_key)
        try:
            completion = await client.chat.completions.create(
                model=settings.ai_model,
                messages=[
                    {"role": "system", "content": build_system_prompt(req.context)},
                    {"role": "user", "content": req.message},
                ],
                temperature=0.7,
                max_tokens=1000,
            )
            return ChatResponse(
                response=completion.choices[0].message.content,
                provider="openai",
            )
        except openai.AuthenticationError:
            raise HTTPException(status_code=401, detail="API Key da OpenAI inválida")
        except openai.RateLimitError:
            raise HTTPException(status_code=429, detail="Rate limit da OpenAI atingido")
        except openai.APIError as e:
            raise HTTPException(status_code=502, detail=f"Erro OpenAI: {str(e)}")

    elif provider == "ollama":
        try:
            async with httpx.AsyncClient(timeout=60.0) as client:
                resp = await client.post(
                    f"{settings.ollama_base_url}/api/chat",
                    json={
                        "model": settings.ollama_model,
                        "messages": [
                            {"role": "system", "content": build_system_prompt(req.context)},
                            {"role": "user", "content": req.message},
                        ],
                        "stream": False,
                    },
                )
                resp.raise_for_status()
                data = resp.json()
                return ChatResponse(
                    response=data["message"]["content"],
                    provider="ollama",
                )
        except httpx.ConnectError:
            raise HTTPException(status_code=503, detail="Ollama não está rodando em localhost:11434")
        except Exception as e:
            raise HTTPException(status_code=502, detail=f"Erro Ollama: {str(e)}")

    else:
        raise HTTPException(status_code=400, detail=f"Provider '{provider}' não suportado")
