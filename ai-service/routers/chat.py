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
        "um sistema de gestão financeira pessoal completo. Você ajuda o administrador "
        "a entender os dados do sistema, analisar padrões financeiros, monitorar a "
        "saúde do negócio e tomar decisões estratégicas.\n\n"

        "SUAS CAPACIDADES:\n"
        "- Analisar receitas, despesas e saldos (mês atual, anterior e evolução 6 meses)\n"
        "- Monitorar cartões de crédito (limites, faturas, utilização)\n"
        "- Avaliar parcelamentos ativos e recorrências\n"
        "- Acompanhar metas financeiras dos usuários (progresso, tipos, status)\n"
        "- Verificar orçamentos por categoria (estourados, percentual geral)\n"
        "- Analisar gamificação (níveis, streaks, conquistas, engajamento)\n"
        "- Monitorar indicações/referral (conversão, pendentes)\n"
        "- Avaliar notificações e campanhas (taxa de leitura, eficácia)\n"
        "- Verificar cupons e blog (descontos ativos, conteúdo publicado)\n"
        "- Acompanhar lançamentos vencidos e taxa de pagamento\n"
        "- Avaliar crescimento de usuários, onboarding e verificação de email\n"
        "- Monitorar assinaturas e MRR (receita recorrente mensal)\n\n"

        "REGRAS:\n"
        "1. Responda sempre em português brasileiro, de forma clara, objetiva e prática.\n"
        "2. Use os números EXATOS fornecidos no contexto. NUNCA invente dados.\n"
        "3. Se um dado não estiver no contexto, diga que não tem essa informação.\n"
        "4. Ao comparar períodos, calcule variações percentuais para dar contexto.\n"
        "5. Destaque alertas: orçamentos estourados, vencidos, cartões perto do limite.\n"
        "6. Sugira ações concretas baseadas nos dados quando relevante.\n"
        "7. Use formatação com negrito e bullet points para respostas mais longas."
    )
    if context:
        base += "\n\n--- DADOS REAIS DO SISTEMA LUKRATO ---\n"
        base += _format_context(context)
        base += "\n--- FIM DOS DADOS ---"
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
                max_tokens=2000,
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
            async with httpx.AsyncClient(timeout=120.0) as client:
                resp = await client.post(
                    f"{settings.ollama_base_url}/api/chat",
                    json={
                        "model": settings.ollama_model,
                        "messages": [
                            {"role": "system", "content": build_system_prompt(req.context)},
                            {"role": "user", "content": req.message},
                        ],
                        "stream": False,
                        "think": False,
                        "options": {
                            "temperature": 0.7,
                            "num_predict": 2048,
                            "num_ctx": 8192,
                        },
                    },
                )
                resp.raise_for_status()
                data = resp.json()

                content = data["message"]["content"]
                # Remove possíveis blocos <think>...</think> residuais
                import re
                content = re.sub(r"<think>.*?</think>", "", content, flags=re.DOTALL).strip()

                return ChatResponse(
                    response=content,
                    provider="ollama",
                )
        except httpx.ConnectError:
            raise HTTPException(status_code=503, detail="Ollama não está rodando em localhost:11434")
        except Exception as e:
            raise HTTPException(status_code=502, detail=f"Erro Ollama: {str(e)}")

    else:
        raise HTTPException(status_code=400, detail=f"Provider '{provider}' não suportado")
