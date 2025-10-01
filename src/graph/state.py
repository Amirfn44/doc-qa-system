from typing import List, TypedDict
from langchain_core.documents import Document

class AgentState(TypedDict):
    query: str
    documents: List[Document]
    answer: str
    citations: List[str]
    utility_request: str
    utility_response: str
