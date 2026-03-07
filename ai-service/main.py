from fastapi import FastAPI, Request, HTTPException
from fastapi.middleware.cors import CORSMiddleware

from config import settings
from routers import chat, analysis, categories

app = FastAPI(
    title="Lukrato AI Service",
    version="1.0.0",
    docs_url="/docs" if True else None,  # desabilite em produção
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
