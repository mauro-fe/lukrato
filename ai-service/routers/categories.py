from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from typing import Optional, List
import httpx

from config import settings

router = APIRouter()

DEFAULT_CATEGORIES = [
    "Alimentação", "Transporte", "Moradia", "Saúde", "Educação",
    "Lazer", "Vestuário", "Investimentos", "Salário", "Freelance",
    "Assinaturas", "Serviços Públicos", "Outros",
]


class CategoryRequest(BaseModel):
    description: str
    available_categories: Optional[List[str]] = None
    provider: Optional[str] = None


class CategoryResponse(BaseModel):
    category: Optional[str]
    provider: str


@router.post("/category", response_model=CategoryResponse)
async def suggest_category(req: CategoryRequest):
    provider = req.provider or settings.ai_provider
    categories = req.available_categories or DEFAULT_CATEGORIES

    prompt = (
        f'Classifique o lançamento financeiro abaixo em UMA das categorias da lista.\n\n'
        f'Descrição: "{req.description}"\n\n'
        f'Categorias: {", ".join(categories)}\n\n'
        f'Responda SOMENTE com o nome exato de uma categoria. Sem ponto final, sem explicação.'
    )

    if provider != "ollama":
        raise HTTPException(status_code=400, detail=f"Provider '{provider}' não suportado. Use o endpoint PHP para OpenAI.")

    try:
        async with httpx.AsyncClient(timeout=60.0) as client:
            resp = await client.post(
                f"{settings.ollama_base_url}/api/chat",
                json={
                    "model": settings.ollama_model,
                    "messages": [
                        {
                            "role": "system",
                            "content": "Você classifica lançamentos financeiros em categorias. Responda apenas com o nome da categoria.",
                        },
                        {"role": "user", "content": prompt},
                    ],
                    "stream": False,
                    "options": {
                        "temperature": 0.1,
                        "num_predict": 20,
                        "num_ctx": 2048,
                    },
                },
            )
            resp.raise_for_status()
            data = resp.json()
            suggested = data["message"]["content"].strip().rstrip(".")
            category = suggested if suggested in categories else None
            return CategoryResponse(category=category, provider="ollama")
    except httpx.ConnectError:
        raise HTTPException(status_code=503, detail="Ollama não está rodando")
    except Exception as e:
        raise HTTPException(status_code=502, detail=f"Erro Ollama: {str(e)}")
