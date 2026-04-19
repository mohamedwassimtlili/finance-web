# utils/guard.py

from models import PromptRequest, Message
from utils.call_llm import call_llm

GUARD_PROMPT = """You are a strict topic classifier for an insurance assistant.

Classify the user message as either:
- "ALLOWED" — if it relates to insurance, coverage, claims, policies, premiums, 
  assets, contracts, deductibles, or general greetings/clarifications in that context
- "BLOCKED" — if it is completely unrelated to insurance (e.g. coding, recipes, 
  politics, general knowledge, math homework, etc.)

Reply with ONLY the single word: ALLOWED or BLOCKED. Nothing else."""

BLOCKED_REPLY = (
    "I'm your insurance assistant and can only help with insurance-related topics "
    "such as coverage, claims, premiums, and policy management. "
    "How can I assist you with your insurance needs?"
)

async def is_insurance_related(message: str) -> bool:
    """Returns True if the message is insurance-related, False otherwise."""
    request = PromptRequest(
        prompt=message,
        history=[],
        system_prompt=GUARD_PROMPT,
        temperature=0.0,   # deterministic classification
        max_tokens=5,      # only needs one word
    )
    response = await call_llm(request)
    verdict = response.reply.strip().upper()
    return verdict == "ALLOWED"