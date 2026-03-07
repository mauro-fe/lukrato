from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from typing import Optional, List
import openai

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
                        "content": "Você classifica lançamentos financeiros em categorias. Responda apenas com o nome da categoria.",
                    },
                    {"role": "user", "content": prompt},
                ],
                temperature=0.1,
                max_tokens=20,
            )
            suggested = completion.choices[0].message.content.strip().rstrip(".")
            # Retorna null se a IA inventou uma categoria fora da lista
            category = suggested if suggested in categories else None
            return CategoryResponse(category=category, provider="openai")

        except openai.APIError as e:
            raise HTTPException(status_code=502, detail=f"Erro OpenAI: {str(e)}")

    else:
        raise HTTPException(status_code=400, detail=f"Provider '{provider}' não suportado")
