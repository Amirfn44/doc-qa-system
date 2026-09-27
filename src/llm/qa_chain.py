from langchain_ollama.llms import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate
from src.llm.config import CHAT_MODEL

model = OllamaLLM(model=CHAT_MODEL)

template = """
You are an expert AI reviewer.
Here are some relevant document chunks: {reviews}
Answer the following question concisely: {question}
"""

prompt = ChatPromptTemplate.from_template(template)

def answer_question(reviews, question):
    chain = prompt | model
    return chain.invoke({"reviews": reviews, "question": question})
