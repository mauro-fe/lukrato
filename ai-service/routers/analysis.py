from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from typing import Optional, List
import openai
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

    if provider == "openai":
        if not settings.openai_api_key:
            raise HTTPException(status_code=503, detail="OPENAI_API_KEY não configurada")

        client = openai.AsyncOpenAI(api_key=settings.openai_api_key)
        try:
            completion = await client.chat.completions.create(
                model=settings.ai_model,
                messages=[
                    {
                        "role": "system",
                        "content": "Você é um analista financeiro especializado. Sempre retorne JSON válido no formato solicitado.",
                    },
                    {"role": "user", "content": prompt},
                ],
                temperature=0.3,
                max_tokens=800,
                response_format={"type": "json_object"},
            )
            result = json.loads(completion.choices[0].message.content)
            return AnalysisResponse(
                insights=result.get("insights", []),
                resumo=result.get("resumo", ""),
                provider="openai",
            )
        except openai.APIError as e:
            raise HTTPException(status_code=502, detail=f"Erro OpenAI: {str(e)}")
        except json.JSONDecodeError:
            raise HTTPException(status_code=502, detail="Resposta da IA não é um JSON válido")
    else:
        raise HTTPException(status_code=400, detail=f"Provider '{provider}' não suportado para análise")
