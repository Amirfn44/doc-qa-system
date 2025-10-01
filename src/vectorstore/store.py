from langchain_ollama import OllamaEmbeddings
from langchain_chroma import Chroma
import os

def build_vector_store(documents, db_location="./db"):
    embeddings = OllamaEmbeddings(model="mxbai-embed-large")

    vector_store = Chroma(
        collection_name="documents_collection",
        persist_directory=db_location,
        embedding_function=embeddings
    )

    if vector_store._collection.count() > 0:
        vector_store.delete(ids=vector_store.get()["ids"])


    if documents:
        vector_store.add_documents(
            documents=documents
        )

    retriever = vector_store.as_retriever(search_kwargs={"k":5})
    return retriever
