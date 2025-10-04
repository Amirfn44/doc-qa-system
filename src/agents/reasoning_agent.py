from langchain_core.prompts import ChatPromptTemplate
from langchain_ollama.llms import OllamaLLM as Ollama
from src.graph.state import AgentState

class ReasoningAgent:
    def __init__(self):
        self.llm = Ollama(model="llama3.2")
        self.prompt = ChatPromptTemplate.from_template(
            """You are an intelligent assistant helping users understand their documents.

You will be given a query and relevant excerpts from documents. Your task is to:
1. Carefully read the ENTIRE query - it may contain multiple questions
2. Analyze all the provided document excerpts to find answers to ALL parts of the query
3. Provide a comprehensive answer that addresses EVERY question asked
4. Write in a clear, conversational style
5. Do NOT include inline citations like [filename.pdf] in your response
6. If the documents contain information for any part of the query, include it in your answer

Query: {query}

Relevant Document Excerpts:
{documents}

Instructions:
- Address ALL questions or parts of the query, not just the first one
- Use information from ALL relevant document excerpts provided
- Write naturally without mentioning source files in the text
- Be thorough and cover each aspect of the query
- If the documents don't contain information for a specific part, clearly state which part is missing
- Organize your answer logically, addressing each question in sequence

Your Complete Answer:"""
        )
        self.chain = self.prompt | self.llm

    def reason(self, state: AgentState):
        query = state["query"]
        documents = state["documents"]

        # Format documents for the prompt
        doc_texts = []
        for i, doc in enumerate(documents, 1):
            doc_texts.append(f"Document {i}:\n{doc.page_content}\n")

        formatted_docs = "\n".join(doc_texts)

        # Get response from LLM
        response = self.chain.invoke({
            "query": query,
            "documents": formatted_docs
        })

        # Extract unique source files from documents
        citations = list(set([doc.metadata["source_file"] for doc in documents]))

        answer = response.strip()

        return {"answer": answer, "citations": citations}
