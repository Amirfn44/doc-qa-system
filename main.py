from langchain_ollama.llms import OllamaLLM
from langchain_core.prompts import ChatPromptTemplate
from vector import retriever

model = OllamaLLM(model="llama3.2")

template = """
you are an expert reviewer for resturant
Here are some relevant review = {review}
Here is the question to answer = {question}
"""

prompt = ChatPromptTemplate.from_template(template)
chain = prompt | model

while True:
    print("\n\n--------------------------------------------")
    question = input("Ask your question (q for quit): ")
    print("\n\n")
    if(question == "q"):
        break
    reviews = retriever.invoke(question)
    res = chain.invoke({"review":reviews,"question":question})

    print(res)