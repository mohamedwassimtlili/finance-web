from typing import Dict, List, Tuple

from models import Message, PromptRequest
from utils.call_llm import call_llm

# ─────────────────────────────────────────────────────────────────────────────
# Insurance System Prompt
# ─────────────────────────────────────────────────────────────────────────────
INSURANCE_SYSTEM_PROMPT = """You are an expert insurance assistant for a fintech platform.
You help users:
- Understand available insurance packages and their coverage details
- Manage their insured assets (vehicles, property, equipment, etc.)
- Submit and track contract requests
- Understand premium calculations (base price × risk multiplier)
- Explain policy terms, deductibles, and claim procedures

Be concise, professional, and empathetic. Ask clarifying questions when needed.
If a user asks something outside insurance, politely redirect them."""

# Per-user in-memory conversation store  (user_id → [{role, content}, ...])
user_memories: Dict[int, List[dict]] = {}


async def get_insurance_reply(user_id: int, message: str) -> Tuple[str, int]:
    """
    Handles one insurance chat turn for the given user.

    Retrieves or initialises the user's conversation history, calls the LLM
    via call_llm, persists the updated history, and returns the assistant's
    reply together with the updated history length.
    """
    if user_id not in user_memories:
        user_memories[user_id] = []

    # Convert stored dicts → Message objects expected by call_llm
    history_messages = [Message(**m) for m in user_memories[user_id]]

    request = PromptRequest(
        prompt=message,
        history=history_messages,
        system_prompt=INSURANCE_SYSTEM_PROMPT,
        temperature=0.7,
        max_tokens=1024,
    )

    response = await call_llm(request)

    # Persist updated history as plain dicts, capped at 20 messages
    user_memories[user_id] = [m.model_dump() for m in response.history][-20:]

    return response.reply, len(user_memories[user_id])


def reset_user_memory(user_id: int) -> None:
    """Clear the conversation memory for the given user."""
    user_memories.pop(user_id, None)


def get_user_history(user_id: int) -> List[dict]:
    """Return the stored conversation history for the given user."""
    return user_memories.get(user_id, [])
