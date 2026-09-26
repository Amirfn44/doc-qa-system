from langchain_ollama import OllamaEmbeddings
from langchain_chroma import Chroma
import os
import shutil
from src.llm.config import EMBEDDING_MODEL
import json

def build_vector_store(documents, db_location="./db/chroma"):
    """
    Build a vector store from documents.
    Always rebuilds the store to ensure fresh data.
    """
    embeddings = OllamaEmbeddings(model=EMBEDDING_MODEL)

    os.makedirs(db_location, exist_ok=True)

    manifest_path = os.path.join(db_location, "documents.manifest.json")
    manifest = sorted([
        {
            "source": doc.metadata.get("source_path", doc.metadata.get("source_file", "")),
            "chunk": doc.metadata.get("chunk_index"),
            "content": doc.page_content,
        }
        for doc in documents
    ], key=lambda item: (item["source"], item["chunk"] or 0))

    if os.path.exists(manifest_path):
        try:
            with open(manifest_path, "r", encoding="utf-8") as file:
                if json.load(file) == manifest:
                    vector_store = Chroma(
                        collection_name="documents_collection",
                        persist_directory=db_location,
                        embedding_function=embeddings,
                    )
                    return vector_store.as_retriever(search_kwargs={"k": 10})
        except (OSError, ValueError):
            pass

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

    with open(manifest_path, "w", encoding="utf-8") as file:
        json.dump(manifest, file, ensure_ascii=False)

    retriever = vector_store.as_retriever(search_kwargs={"k": 10})

    return retriever
