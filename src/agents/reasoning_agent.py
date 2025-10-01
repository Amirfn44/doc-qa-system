from langchain_core.prompts import ChatPromptTemplate
from langchain_ollama.llms import OllamaLLM as Ollama
from src.graph.state import AgentState

class ReasoningAgent:
    def __init__(self):
        self.llm = Ollama(model="llama3.2")
        self.prompt = ChatPromptTemplate.from_template(
            """You are a reasoning agent. You will be given a query and a set of documents.
            Your task is to answer the query based on the information in the documents.
            You must cite the sources you use to answer the query.
            For each sentence in your answer, you must add a citation to the source document.
            The citation should be the `source_file` from the document's metadata.
            For example: "This is a sentence from the document. [source_file.pdf]"

            Query: {query}
            Documents: {documents}

            Chain of Thought:
            ...

            Final Answer with Citations:"""
        )
        self.chain = self.prompt | self.llm

    def reason(self, state: AgentState):
        query = state["query"]
        documents = state["documents"]
        response = self.chain.invoke({"query": query, "documents": documents})


        citations = [doc.metadata["source_file"] for doc in documents if doc.metadata["source_file"] in response]
        answer = response 
        return {"answer": answer, "citations": citations}
