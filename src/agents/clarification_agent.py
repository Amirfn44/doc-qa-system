from langchain_core.prompts import ChatPromptTemplate
from langchain_ollama.llms import OllamaLLM as Ollama
from src.graph.state import AgentState

class ClarificationAgent:
    def __init__(self):
        self.llm = Ollama(model="llama3.2")
        self.prompt = ChatPromptTemplate.from_template(
            """You are a clarification agent. Your purpose is to ask clarifying questions when you don't understand a user's query.
            The user's query is: {query}
            You were unable to find any relevant documents for this query.
            Please ask a clarifying question to the user. For example, you could ask them to rephrase the question or provide more context."""
        )
        self.chain = self.prompt | self.llm

    def clarify(self, state: AgentState):
        query = state["query"]
        clarification = self.chain.invoke({"query": query})
        return {"answer": clarification}
