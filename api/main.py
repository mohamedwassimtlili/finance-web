from fastapi import FastAPI, HTTPException
import httpx
import os
from pathlib import Path
from dotenv import load_dotenv
from models import PromptRequest, PromptResponse, Message, InsuranceChatRequest, InsuranceChatResponse
from services.insurance import get_insurance_reply, reset_user_memory, get_user_history

# Load .env from the same directory as this file, regardless of where uvicorn is launched
load_dotenv(dotenv_path=Path(__file__).resolve().parent / ".env")

GROQ_API_KEY = os.getenv("GROQ_API_KEY")
GROQ_API_URL = os.getenv(
    "GROQ_API_URL",
    "https://api.groq.com/openai/v1/chat/completions"
)
GROQ_MODEL = os.getenv("GROQ_MODEL", "llama-3.3-70b-versatile")

# Validate configuration at startup
if not GROQ_API_KEY:
    raise RuntimeError("GROQ_API_KEY is not set in the environment.")

app = FastAPI(
    title="FinanceApp AI Service",
    description="FastAPI microservice for interacting with the Groq API.",
    version="1.0.0"
)


# -----------------------------
# Health Check Endpoint
# -----------------------------
@app.get("/health")
async def health_check():
    return {"status": "ok"}

# -----------------------------
# Groq Chat Endpoint
# -----------------------------
@app.post("/chat", response_model=PromptResponse)
async def chat_with_groq(request: PromptRequest):
    """
    Sends a prompt to the Groq API and returns the generated response.
    """

    # Construct the messages payload
    messages = [
        {"role": "system", "content": request.system_prompt},
        *[msg.dict() for msg in request.history],
        {"role": "user", "content": request.prompt},
    ]

    try:
        async with httpx.AsyncClient(timeout=30.0) as client:
            response = await client.post(
                GROQ_API_URL,
                headers={
                    "Authorization": f"Bearer {GROQ_API_KEY}",
                    "Content-Type": "application/json",
                },
                json={
                    "model": GROQ_MODEL,
                    "messages": messages,
                    "temperature": request.temperature,
                    "max_tokens": request.max_tokens,
                },
            )

        # Raise an exception for non-2xx responses
        response.raise_for_status()
        data = response.json()

        # Extract the assistant's reply
        reply = data.get("choices", [{}])[0].get("message", {}).get("content")
        if not reply:
            raise HTTPException(
                status_code=500,
                detail="Invalid response structure from Groq API."
            )

        # Update conversation history
        updated_history = request.history + [
            Message(role="user", content=request.prompt),
            Message(role="assistant", content=reply),
        ]

        # Keep only the last 20 messages to manage token usage
        updated_history = updated_history[-20:]

        return PromptResponse(
            reply=reply,
            history=updated_history
        )

    except httpx.HTTPStatusError as e:
        raise HTTPException(
            status_code=e.response.status_code,
            detail=f"Groq API error: {e.response.text}"
        )
    except httpx.RequestError as e:
        raise HTTPException(
            status_code=500,
            detail=f"Request error while contacting Groq API: {str(e)}"
        )
    except Exception as e:
        raise HTTPException(
            status_code=500,
            detail=f"Unexpected error: {str(e)}"
        )


# ─────────────────────────────────────────────────────────────────────────────
# Insurance Assistant
# ─────────────────────────────────────────────────────────────────────────────

@app.post("/insurance/chat", response_model=InsuranceChatResponse)
async def insurance_chat(request: InsuranceChatRequest):
    """Stateful insurance assistant — delegates memory and LLM logic to the service layer."""
    reply, history_length = await get_insurance_reply(request.user_id, request.message)
    return InsuranceChatResponse(
        reply=reply,
        user_id=request.user_id,
        history_length=history_length,
    )


@app.post("/insurance/reset/{user_id}")
async def reset_insurance_memory(user_id: int):
    """Clear the conversation memory for a specific user."""
    reset_user_memory(user_id)
    return {"status": "memory cleared", "user_id": user_id}


@app.get("/insurance/history/{user_id}")
async def get_insurance_history(user_id: int):
    """Return the current conversation history for a user (debug/admin use)."""
    return {"user_id": user_id, "history": get_user_history(user_id)}
