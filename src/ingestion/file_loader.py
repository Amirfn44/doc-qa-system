import fitz
from docx import Document as DocxDocument
from PIL import Image
import pytesseract
import pandas as pd

pytesseract.pytesseract.tesseract_cmd = r"D:\Program Files\Tesseract-OCR\tesseract.exe"

def load_pdf(file_path):
    doc = fitz.open(file_path)
    return [page.get_text() for page in doc]

def load_docx(file_path):
    doc = DocxDocument(file_path)
    paragraphs = [p.text for p in doc.paragraphs if p.text.strip()]
    return ["\n".join(paragraphs)]

def load_txt(file_path):
    with open(file_path, "r", encoding="utf-8") as f:
        return [f.read()]

def load_csv(file_path):
    df = pd.read_csv(file_path)
    texts = []
    for _, row in df.iterrows():
        combined = " ".join([f"{col}: {row[col]}" for col in df.columns])
        texts.append(combined)
    return texts

def load_image(file_path):
    img = Image.open(file_path)
    text = pytesseract.image_to_string(img)
    return [text]

def load_xlsx(file_path):
    df = pd.read_excel(file_path)
    texts = []
    for _, row in df.iterrows():
        combined = " ".join([f"{col}: {row[col]}" for col in df.columns])
        texts.append(combined)
    return texts
