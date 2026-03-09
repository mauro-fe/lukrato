from fastapi import FastAPI, Request, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from contextlib import asynccontextmanager
import httpx
import logging

from config import settings
from routers import chat, analysis, categories

logger = logging.getLogger("lukrato-ai")


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Verifica conectividade com Ollama ao iniciar o serviço."""
    if settings.ai_provider == "ollama":
        try:
            async with httpx.AsyncClient(timeout=5.0) as client:
                resp = await client.get(f"{settings.ollama_base_url}/api/tags")
                resp.raise_for_status()
                models = [m["name"] for m in resp.json().get("models", [])]
                logger.info(f"Ollama conectado. Modelos disponíveis: {models}")
                if settings.ollama_model not in models:
                    base_model = settings.ollama_model.split(":")[0]
                    matching = [m for m in models if m.startswith(base_model)]
                    if not matching:
                        logger.warning(
                            f"Modelo '{settings.ollama_model}' não encontrado no Ollama. "
                            f"Disponíveis: {models}"
                        )
        except httpx.ConnectError:
            logger.warning(
                f"Ollama não está rodando em {settings.ollama_base_url}. "
                "O serviço vai iniciar, mas as requisições vão falhar."
            )
        except Exception as e:
            logger.warning(f"Erro ao verificar Ollama: {e}")
    yield


app = FastAPI(
    title="Lukrato AI Service",
    version="1.0.0",
    docs_url="/docs" if settings.debug else None,
    redoc_url="/redoc" if settings.debug else None,
    lifespan=lifespan,
)

# Só aceita requisições de localhost (PHP roda localmente)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost", "http://localhost:80", "http://127.0.0.1"],
    allow_methods=["GET", "POST"],
    allow_headers=["*"],
)


@app.middleware("http")
async def verify_internal_token(request: Request, call_next):
    """Garante que só o PHP local possa chamar este serviço."""
    if request.url.path == "/health":
        return await call_next(request)

    if settings.ai_internal_token:
        token = request.headers.get("Authorization", "").removeprefix("Bearer ").strip()
        if token != settings.ai_internal_token:
            raise HTTPException(status_code=401, detail="Token interno inválido")

    return await call_next(request)


app.include_router(chat.router, prefix="/chat", tags=["chat"])
app.include_router(analysis.router, prefix="/analyze", tags=["analysis"])
app.include_router(categories.router, prefix="/suggest", tags=["categories"])


@app.get("/health")
def health():
    return {
        "status": "ok",
        "service": "lukrato-ai",
        "provider": settings.ai_provider,
        "model": settings.ai_model if settings.ai_provider == "openai" else settings.ollama_model,
    }
