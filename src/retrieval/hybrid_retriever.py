from langchain_community.retrievers import BM25Retriever
from langchain.retrievers import EnsembleRetriever
from langchain_cohere import CohereRerank
from langchain_core.documents import Document

class HybridRetriever:
    def __init__(self, vector_retriever, documents):
        self.vector_retriever = vector_retriever
        self.bm25_retriever = BM25Retriever.from_documents(documents)
        self.bm25_retriever.k = 10  # Retrieve more candidates

        self.ensemble_retriever = EnsembleRetriever(
            retrievers=[self.bm25_retriever, self.vector_retriever],
            weights=[0.5, 0.5]
        )
        self.reranker = CohereRerank(model="rerank-english-v3.0")

    def retrieve(self, query: str, k: int = 8):
        """
        Retrieve relevant documents using hybrid search.

        Args:
            query: The search query
            k: Number of top documents to return after reranking (default 8 for better coverage)

        Returns:
            List of top-k reranked documents
        """
        docs = self.ensemble_retriever.invoke(query)

        reranked_docs = self.reranker.compress_documents(documents=docs, query=query)

        return reranked_docs[:k]
