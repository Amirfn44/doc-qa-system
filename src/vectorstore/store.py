from langchain_ollama import OllamaEmbeddings
from langchain_chroma import Chroma
import os
import shutil

def build_vector_store(documents, db_location="./db/chroma"):
    """
    Build a vector store from documents.
    Always rebuilds the store to ensure fresh data.
    """
    embeddings = OllamaEmbeddings(model="mxbai-embed-large")

    os.makedirs(db_location, exist_ok=True)

    if os.path.exists(db_location):
        try:
            for item in os.listdir(db_location):
                item_path = os.path.join(db_location, item)
                if os.path.isfile(item_path) or os.path.islink(item_path):
                    os.unlink(item_path)
                elif os.path.isdir(item_path):
                    shutil.rmtree(item_path)
        except Exception as e:
            print(f"Warning: Could not clear existing database: {e}")

    vector_store = Chroma(
        collection_name="documents_collection",
        persist_directory=db_location,
        embedding_function=embeddings
    )

    if documents:
        vector_store.add_documents(documents=documents)
        print(f"Added {len(documents)} documents to vector store")

    retriever = vector_store.as_retriever(search_kwargs={"k": 10})

    return retriever
