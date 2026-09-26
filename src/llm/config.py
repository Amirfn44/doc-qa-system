import os


CHAT_MODEL = os.getenv("OLLAMA_CHAT_MODEL", "llama3.2")
EMBEDDING_MODEL = os.getenv("OLLAMA_EMBEDDING_MODEL", "mxbai-embed-large")
ANSWER_LANGUAGE = os.getenv("QA_LANGUAGE", "English")
