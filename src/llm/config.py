import os

from dotenv import load_dotenv


load_dotenv()

CHAT_MODEL = os.getenv("OLLAMA_CHAT_MODEL", "qwen3:14b")
EMBEDDING_MODEL = os.getenv("OLLAMA_EMBEDDING_MODEL", "qwen3-embedding:latest")
ANSWER_LANGUAGE = os.getenv("QA_LANGUAGE", "English")
