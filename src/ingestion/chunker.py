from langchain.text_splitter import RecursiveCharacterTextSplitter

splitter = RecursiveCharacterTextSplitter(chunk_size=500, chunk_overlap=50)

def chunk_text(texts):
    chunks = []
    for text in texts:
        chunks.extend(splitter.split_text(text))
    return chunks
