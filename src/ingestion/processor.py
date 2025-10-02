import os
import uuid
from langchain_core.documents import Document
from src.ingestion.file_loader import load_pdf, load_docx, load_txt, load_csv, load_image, load_xlsx
from src.ingestion.chunker import chunk_text

def detect_file_type(file_path):
    ext = os.path.splitext(file_path)[1].lower()
    if ext in [".pdf"]:
        return "pdf"
    elif ext in [".docx"]:
        return "docx"
    elif ext in [".txt"]:
        return "txt"
    elif ext in [".csv"]:
        return "csv"
    elif ext in [".xlsx"]:
        return "xlsx"
    elif ext in [".png", ".jpg", ".jpeg", ".tiff", ".bmp"]:
        return "image"
    else:
        return "unknown"

def process_file(file_path):
    file_type = detect_file_type(file_path)
    if file_type == "pdf":
        texts = load_pdf(file_path)
    elif file_type == "docx":
        texts = load_docx(file_path)
    elif file_type == "txt":
        texts = load_txt(file_path)
    elif file_type == "csv":
        texts = load_csv(file_path)
    elif file_type == "xlsx":
        texts = load_xlsx(file_path)
    elif file_type == "image":
        texts = load_image(file_path)
    else:
        raise ValueError(f"Unsupported file type: {file_type}")

    documents = []
    chunks = chunk_text(texts)
    for idx, chunk in enumerate(chunks):
        documents.append(Document(
            page_content=chunk,
            metadata={"source_file": os.path.basename(file_path), "chunk_index": idx},
            id=str(uuid.uuid4())
        ))
    return documents
