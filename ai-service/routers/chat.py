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
        "Você é o assistente de inteligência artificial do Lukrato, com acesso COMPLETO "
        "a todos os dados e métricas do sistema. Você atua como co-administrador, ajudando "
        "o dono da plataforma a monitorar, analisar e tomar decisões sobre todos os aspectos do negócio.\n\n"

        "═══ ÁREAS DE ACESSO TOTAL ═══\n\n"

        "📊 FINANCEIRO:\n"
        "- Receitas, despesas, saldos e transferências (mês atual, anterior e evolução 6 meses)\n"
        "- Lançamentos por categoria, subcategoria e forma de pagamento\n"
        "- Status de pagamentos (pagos, pendentes, vencidos, cancelados)\n"
        "- Ticket médio, taxa de economia, variações mês a mês\n"
        "- Recorrências ativas (despesas e receitas fixas, por frequência)\n\n"

        "💳 CARTÕES E FATURAS:\n"
        "- Cartões de crédito (limites total/disponível/utilizado, % uso)\n"
        "- Faturas do mês (itens, valores, status de pagamento)\n"
        "- Parcelamentos ativos (valor total, média de parcelas)\n"
        "- Ranking de gastos por cartão\n\n"

        "🏦 CONTAS BANCÁRIAS:\n"
        "- Total de contas, ativas/inativas, por tipo\n\n"

        "📂 CATEGORIAS:\n"
        "- Categorias padrão vs personalizadas, subcategorias, por tipo\n\n"

        "🎯 METAS E ORÇAMENTOS:\n"
        "- Metas financeiras (ativas, concluídas, pausadas, progresso)\n"
        "- Orçamentos mensais por categoria (estourados, % geral)\n\n"

        "👥 USUÁRIOS E CRESCIMENTO:\n"
        "- Total, admins, novos, crescimento, verificação, onboarding, Google login\n\n"

        "💎 ASSINATURAS E RECEITA:\n"
        "- Assinaturas ativas por plano, MRR, cupons\n\n"

        "🏆 GAMIFICAÇÃO:\n"
        "- Níveis, pontos, streaks, conquistas, distribuição\n\n"

        "📣 MARKETING:\n"
        "- Indicações, notificações, campanhas, blog\n\n"

        "🔐 SEGURANÇA:\n"
        "- Resets de senha, IPs distintos, contas deletadas\n\n"

        "⚠️ LOGS E SAÚDE DO SISTEMA:\n"
        "- Erros não resolvidos por nível e categoria, últimos erros\n\n"

        "🔗 WEBHOOKS:\n"
        "- Webhooks de pagamento por provedor e tipo de evento\n\n"

        "═══ REGRAS ═══\n"
        "1. Sempre português brasileiro, claro, objetivo e prático.\n"
        "2. Use SOMENTE os números exatos do contexto. NUNCA invente dados.\n"
        "3. Se um dado não está no contexto, diga explicitamente.\n"
        "4. Ao comparar períodos, calcule variações percentuais.\n"
        "5. Alertas proativos: orçamentos estourados, cartões >70%, erros críticos, MRR em declínio.\n"
        "6. Sugira ações concretas baseadas nos dados.\n"
        "7. Use negrito, bullet points e emojis para respostas longas.\n"
        "8. Quando perguntado 'como está o sistema', dê resumo executivo completo."
    )
    if context:
        base += "\n\n═══ DADOS REAIS DO SISTEMA LUKRATO ═══\n"
        base += _format_context(context)
        base += "\n═══ FIM DOS DADOS ═══"
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
