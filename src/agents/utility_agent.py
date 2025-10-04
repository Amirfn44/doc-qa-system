from langchain_core.prompts import ChatPromptTemplate
from langchain_ollama.llms import OllamaLLM as Ollama
from src.graph.state import AgentState

class UtilityAgent:
    def __init__(self):
        self.llm = Ollama(model="llama3.2")
        self.summarization_prompt = ChatPromptTemplate.from_template(
            """You are a utility agent. You will be given a text.
            Your task is to summarize the text.

            Text: {text}

            Summary:"""
        )
        self.summarization_chain = self.summarization_prompt | self.llm

    def summarize(self, state: AgentState):
        text = state["answer"] 
        summary = self.summarization_chain.invoke({"text": text})
        return {"utility_response": summary}
