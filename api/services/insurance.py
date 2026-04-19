from typing import Dict, List, Tuple

from models import Message, PromptRequest,UserContext
from utils.call_llm import call_llm
from utils.guard import is_insurance_related, BLOCKED_REPLY
from utils.intent import detect_intent, Intent, BLOCKED_REPLY
from utils.context_builder import build_context_block

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

async def get_insurance_reply(
    user_id: int,
    message: str,
    context: UserContext,
) -> Tuple[str, int]:

    # 1. Merged guard + intent — one LLM call
    intent = await detect_intent(message)

    if intent == Intent.BLOCKED:
        return BLOCKED_REPLY, len(user_memories.get(user_id, []))

    # 2. Slice and format only the relevant data
    context_block = build_context_block(intent, context.model_dump())

    # 3. Inject context into the user prompt
    enriched_prompt = (
        f"{context_block}\n\nUser question: {message}"
        if context_block else message
    )

    # 4. Call main LLM with history
    if user_id not in user_memories:
        user_memories[user_id] = []

    history_messages = [Message(**m) for m in user_memories[user_id]]
    request = PromptRequest(
        prompt=enriched_prompt,
        history=history_messages,
        system_prompt=INSURANCE_SYSTEM_PROMPT,
        temperature=0.7,
        max_tokens=1024,
    )

    response = await call_llm(request)
    user_memories[user_id] = [m.model_dump() for m in response.history][-20:]
    return response.reply, len(user_memories[user_id])


def reset_user_memory(user_id: int) -> None:
    """Clear the conversation memory for the given user."""
    user_memories.pop(user_id, None)


def get_user_history(user_id: int) -> List[dict]:
    """Return the stored conversation history for the given user."""
    return user_memories.get(user_id, [])
