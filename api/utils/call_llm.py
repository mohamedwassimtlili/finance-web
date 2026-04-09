# utils/call_llm.py
import os
import httpx
from pathlib import Path
from typing import List
from dotenv import load_dotenv
from fastapi import HTTPException

from models import Message, PromptRequest, PromptResponse

# Load .env from the api/ directory (one level up from utils/)
load_dotenv(dotenv_path=Path(__file__).resolve().parent.parent / ".env")

GROQ_API_KEY = os.getenv("GROQ_API_KEY")
GROQ_API_URL = os.getenv(
    "GROQ_API_URL",
    "https://api.groq.com/openai/v1/chat/completions"
)
GROQ_MODEL = os.getenv("GROQ_MODEL", "llama-3.3-70b-versatile")

if not GROQ_API_KEY:
    raise RuntimeError("GROQ_API_KEY environment variable is not set.")


async def call_llm(request: PromptRequest) -> PromptResponse:
    """
    Sends a prompt to the Groq API and returns the assistant's response.

    Args:
        request (PromptRequest): The structured request containing the user prompt,
                                 conversation history, and generation parameters.

    Returns:
        PromptResponse: The assistant's reply along with the updated conversation history.
    """

    # Construct the messages payload
    messages: List[dict] = [
        {"role": "system", "content": request.system_prompt},
        *[msg.model_dump() for msg in request.history],
        {"role": "user", "content": request.prompt},
    ]

    payload = {
        "model": GROQ_MODEL,
        "messages": messages,
        "temperature": request.temperature,
        "max_tokens": request.max_tokens,
    }

    headers = {
        "Authorization": f"Bearer {GROQ_API_KEY}",
        "Content-Type": "application/json",
    }

    try:
        async with httpx.AsyncClient(timeout=30.0) as client:
            response = await client.post(
                GROQ_API_URL,
                headers=headers,
                json=payload,
            )

        # Raise exception for non-success status codes
        response.raise_for_status()
        data = response.json()

        # Extract assistant reply
        reply = (
            data.get("choices", [{}])[0]
            .get("message", {})
            .get("content")
        )

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

        # Limit history to the last 20 messages
        updated_history = updated_history[-20:]

        return PromptResponse(
            reply=reply,
            history=updated_history
        )

    except httpx.HTTPStatusError as exc:
        raise HTTPException(
            status_code=exc.response.status_code,
            detail=f"Groq API error: {exc.response.text}"
        )

    except httpx.RequestError as exc:
        raise HTTPException(
            status_code=500,
            detail=f"Error communicating with Groq API: {str(exc)}"
        )

    except Exception as exc:
        raise HTTPException(
            status_code=500,
            detail=f"Unexpected error: {str(exc)}"
        )