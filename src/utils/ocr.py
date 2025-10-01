from PIL import Image
import pytesseract

def ocr_image(file_path):
    img = Image.open(file_path)
    text = pytesseract.image_to_string(img)
    return text
