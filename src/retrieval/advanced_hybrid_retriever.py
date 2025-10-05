from langchain_community.retrievers import BM25Retriever
from langchain.retrievers import EnsembleRetriever
from langchain_cohere import CohereRerank
from langchain_core.documents import Document
from typing import List
import numpy as np

class AdvancedHybridRetriever:
    """
    Advanced hybrid retriever with:
    - RRF (Reciprocal Rank Fusion) for combining results
    - MMR (Maximal Marginal Relevance) for diversity
    - Document reranking with Cohere
    """

    def __init__(self, vector_retriever, documents, use_mmr=True, mmr_lambda=0.5):
        self.vector_retriever = vector_retriever
        self.documents = documents
        self.use_mmr = use_mmr
        self.mmr_lambda = mmr_lambda

        self.bm25_retriever = BM25Retriever.from_documents(documents)
        self.bm25_retriever.k = 15

        self.reranker = CohereRerank(
            model="rerank-english-v3.0",
            top_n=10
        )

    def reciprocal_rank_fusion(
        self,
        results_lists: List[List[Document]],
        k: int = 60
    ) -> List[Document]:
        """
        Apply Reciprocal Rank Fusion to combine multiple ranked lists.
        RRF formula: RRF(d) = sum(1 / (k + rank(d)))

        Args:
            results_lists: List of ranked document lists
            k: Constant to prevent high rank bias (typically 60)

        Returns:
            Fused and ranked list of documents
        """
        doc_scores = {}

        for results in results_lists:
            for rank, doc in enumerate(results, start=1):
                doc_id = id(doc)

                if doc_id not in doc_scores:
                    doc_scores[doc_id] = {
                        'doc': doc,
                        'score': 0
                    }

                doc_scores[doc_id]['score'] += 1.0 / (k + rank)

        sorted_docs = sorted(
            doc_scores.values(),
            key=lambda x: x['score'],
            reverse=True
        )

        return [item['doc'] for item in sorted_docs]

    def maximal_marginal_relevance(
        self,
        query: str,
        documents: List[Document],
        k: int = 8
    ) -> List[Document]:
        """
        Apply MMR to select diverse documents.
        MMR = λ * relevance(doc, query) - (1-λ) * max_similarity(doc, selected)

        Args:
            query: Search query
            documents: Candidate documents
            k: Number of documents to return

        Returns:
            Diverse set of k documents
        """
        if len(documents) <= k:
            return documents

        selected = []
        remaining = documents.copy()

        selected.append(remaining.pop(0))

        while len(selected) < k and remaining:
            mmr_scores = []

            for doc in remaining:
                relevance = 1.0 / (documents.index(doc) + 1)

                max_similarity = 0
                for selected_doc in selected:
                    similarity = self._calculate_similarity(doc, selected_doc)
                    max_similarity = max(max_similarity, similarity)

                mmr = self.mmr_lambda * relevance - (1 - self.mmr_lambda) * max_similarity
                mmr_scores.append((doc, mmr))

            best_doc = max(mmr_scores, key=lambda x: x[1])[0]
            selected.append(best_doc)
            remaining.remove(best_doc)

        return selected

    def _calculate_similarity(self, doc1: Document, doc2: Document) -> float:
        """
        Calculate simple word overlap similarity between two documents.
        """
        words1 = set(doc1.page_content.lower().split())
        words2 = set(doc2.page_content.lower().split())

        if not words1 or not words2:
            return 0.0

        intersection = len(words1 & words2)
        union = len(words1 | words2)

        return intersection / union if union > 0 else 0.0

    def retrieve(self, query: str, k: int = 8) -> List[Document]:
        """
        Retrieve documents using advanced hybrid search.

        Process:
        1. Get results from both vector and BM25 retrievers
        2. Apply RRF to fuse results
        3. Rerank with Cohere
        4. Apply MMR for diversity (optional)
        5. Return top k documents

        Args:
            query: Search query
            k: Number of documents to return

        Returns:
            List of top k relevant and diverse documents
        """
        vector_results = self.vector_retriever.invoke(query)
        bm25_results = self.bm25_retriever.invoke(query)

        fused_results = self.reciprocal_rank_fusion(
            [vector_results, bm25_results],
            k=60
        )

        candidates = fused_results[:20]

        try:
            reranked_docs = self.reranker.compress_documents(
                documents=candidates,
                query=query
            )
        except Exception as e:
            print(f"Reranking failed: {e}, using fused results")
            reranked_docs = candidates

        if self.use_mmr and len(reranked_docs) > k:
            final_docs = self.maximal_marginal_relevance(
                query=query,
                documents=reranked_docs,
                k=k
            )
        else:
            final_docs = reranked_docs[:k]

        return final_docs
