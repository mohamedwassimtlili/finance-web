from pydantic import BaseModel
from typing import List, Optional
# -----------------------------
# Request & Response Schemas
# -----------------------------
class Message(BaseModel):
    role: str  # 'system', 'user', or 'assistant'
    content: str

class PromptRequest(BaseModel):
    prompt: str
    history: Optional[List[Message]] = []
    system_prompt: Optional[str] = (
        "You are a helpful and professional insurance assistant."
    )
    temperature: Optional[float] = 0.7
    max_tokens: Optional[int] = 1024

class PromptResponse(BaseModel):
    reply: str
    history: List[Message]

# -----------------------------
# Insurance Assistant Schemas
# -----------------------------
class InsuranceChatRequest(BaseModel):
    user_id: int
    message: str

class InsuranceChatResponse(BaseModel):
    reply: str
    user_id: int
    history_length: int
