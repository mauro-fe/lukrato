from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from typing import Optional, List
import httpx
import json

from config import settings

router = APIRouter()


class LancamentoSummary(BaseModel):
    categoria: str
    total: float
    count: int
    mes: str


class AnalysisRequest(BaseModel):
    lancamentos: List[LancamentoSummary]
    periodo: Optional[str] = "último mês"
    provider: Optional[str] = None


class AnalysisResponse(BaseModel):
    insights: List[str]
    resumo: str
    provider: str
    tokens_prompt: Optional[int] = None
    tokens_completion: Optional[int] = None


@router.post("/spending", response_model=AnalysisResponse)
async def analyze_spending(req: AnalysisRequest):
    provider = req.provider or settings.ai_provider

    data_text = json.dumps(
        [l.model_dump() for l in req.lancamentos],
        ensure_ascii=False,
        indent=2,
    )

    prompt = f"""Analise os seguintes dados financeiros do período: {req.periodo}

{data_text}

Forneça uma análise financeira útil com:
1. De 3 a 5 insights práticos e acionáveis sobre os padrões de gastos
2. Um resumo executivo em 2 frases

Responda APENAS em JSON com o formato exato:
{{"insights": ["insight 1", "insight 2", "insight 3"], "resumo": "resumo em 2 frases aqui"}}"""

    if provider != "ollama":
        raise HTTPException(status_code=400, detail=f"Provider '{provider}' não suportado. Use o endpoint PHP para OpenAI.")

    try:
        async with httpx.AsyncClient(timeout=120.0) as client:
            resp = await client.post(
                f"{settings.ollama_base_url}/api/chat",
                json={
                    "model": settings.ollama_model,
                    "messages": [
                        {
                            "role": "system",
                            "content": "Você é um analista financeiro especializado. Sempre retorne JSON válido no formato solicitado.",
                        },
                        {"role": "user", "content": prompt},
                    ],
                    "stream": False,
                    "format": "json",
                    "options": {
                        "temperature": 0.3,
                        "num_predict": 800,
                        "num_ctx": 4096,
                    },
                },
            )
            resp.raise_for_status()
            data = resp.json()
            content = data["message"]["content"]
            result = json.loads(content)

            insights = result.get("insights", [])
            if not isinstance(insights, list):
                insights = [str(insights)]
            resumo = result.get("resumo", "")
            if not isinstance(resumo, str):
                resumo = str(resumo)

            return AnalysisResponse(
                insights=insights,
                resumo=resumo,
                provider="ollama",
                tokens_prompt=data.get("prompt_eval_count"),
                tokens_completion=data.get("eval_count"),
            )
    except httpx.ConnectError:
        raise HTTPException(status_code=503, detail="Ollama não está rodando")
    except json.JSONDecodeError:
        raise HTTPException(status_code=502, detail="Resposta do Ollama não é um JSON válido")
    except Exception as e:
        raise HTTPException(status_code=502, detail=f"Erro Ollama: {str(e)}")
