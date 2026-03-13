from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from typing import Optional, List
import httpx

from config import settings

router = APIRouter()

# Fallback genérico — usado apenas quando nenhuma categoria é passada pelo caller.
# O PHP deve sempre enviar available_categories com as categorias reais do usuário.
_FALLBACK_CATEGORIES = [
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
    raw_suggestion: Optional[str] = None
    provider: str
    tokens_prompt: Optional[int] = None
    tokens_completion: Optional[int] = None


@router.post("/category", response_model=CategoryResponse)
async def suggest_category(req: CategoryRequest):
    provider = req.provider or settings.ai_provider
    categories = req.available_categories or _FALLBACK_CATEGORIES

    prompt = (
        f'Classifique o lançamento financeiro abaixo usando a lista de categorias.\n\n'
        f'Descrição: "{req.description}"\n\n'
        f'Categorias disponíveis: {", ".join(categories)}\n\n'
        f'Se houver uma subcategoria adequada (formato "Categoria > Subcategoria"), use-a. '
        f'Caso contrário, use apenas a categoria principal.\n'
        f'Responda SOMENTE com o nome exato como aparece na lista. Sem ponto final, sem explicação.'
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
                            "content": "Você classifica lançamentos financeiros em categorias e subcategorias. Responda com \"Categoria\" ou \"Categoria > Subcategoria\". Nada mais.",
                        },
                        {"role": "user", "content": prompt},
                    ],
                    "stream": False,
                    "options": {
                        "temperature": 0.1,
                        "num_predict": 40,
                        "num_ctx": 2048,
                    },
                },
            )
            resp.raise_for_status()
            data = resp.json()
            suggested = data["message"]["content"].strip().rstrip(".")
            # Match exato ou fuzzy (a IA pode retornar "Categoria > Subcategoria")
            category = suggested if suggested in categories else None
            if not category:
                # Tentar match pela categoria raiz (parte antes do ">")
                root = suggested.split(">")[0].strip() if ">" in suggested else None
                if root and root in categories:
                    category = suggested
            return CategoryResponse(
                category=category,
                raw_suggestion=suggested,
                provider="ollama",
                tokens_prompt=data.get("prompt_eval_count"),
                tokens_completion=data.get("eval_count"),
            )
    except httpx.ConnectError:
        raise HTTPException(status_code=503, detail="Ollama não está rodando")
    except Exception as e:
        raise HTTPException(status_code=502, detail=f"Erro Ollama: {str(e)}")
