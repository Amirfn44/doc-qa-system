from langchain_core.prompts import ChatPromptTemplate
from langchain_ollama.llms import OllamaLLM as Ollama
from src.graph.state import AgentState

class ReasoningAgent:
    def __init__(self):
        self.llm = Ollama(model="llama3.2")
        self.prompt = ChatPromptTemplate.from_template(
            """You are an intelligent assistant helping users understand their documents.

You will be given a query and relevant excerpts from documents. Your task is to:
1. Analyze all the provided document excerpts carefully
2. Synthesize the information to provide a comprehensive, natural answer
3. Write in a clear, conversational style
4. Do NOT include inline citations like [filename.pdf] in your response
5. Simply provide the answer based on the documents

Query: {query}

Relevant Document Excerpts:
{documents}

Instructions:
- Provide a clear, direct answer to the query
- Use information from ALL relevant documents
- Write naturally without mentioning source files in the text
- Be concise but thorough
- If the documents don't contain enough information, say so

Your Answer:"""
        )
        self.chain = self.prompt | self.llm

    def reason(self, state: AgentState):
        query = state["query"]
        documents = state["documents"]

        doc_texts = []
        for i, doc in enumerate(documents, 1):
            doc_texts.append(f"Document {i}:\n{doc.page_content}\n")

        formatted_docs = "\n".join(doc_texts)

        response = self.chain.invoke({
            "query": query,
            "documents": formatted_docs
        })

        citations = list(set([doc.metadata["source_file"] for doc in documents]))

        answer = response.strip()

        return {"answer": answer, "citations": citations}
