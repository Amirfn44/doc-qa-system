from src.retrieval.hybrid_retriever import HybridRetriever
from src.graph.state import AgentState

class RetrieverAgent:
    def __init__(self, hybrid_retriever: HybridRetriever):
        self.hybrid_retriever = hybrid_retriever

    def retrieve(self, state: AgentState):
        query = state["query"]
        documents = self.hybrid_retriever.retrieve(query)
        return {"documents": documents}
