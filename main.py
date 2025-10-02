import sys
import os
from dotenv import load_dotenv
from src.ingestion.processor import process_file
from src.vectorstore.store import build_vector_store
from src.retrieval.hybrid_retriever import HybridRetriever
from src.graph.workflow import create_workflow

load_dotenv()

if "COHERE_API_KEY" not in os.environ:
    sys.stderr.write("Error: COHERE_API_KEY environment variable not set\n")
    sys.exit(1)

if len(sys.argv) > 2 and sys.argv[2]:
    files = [sys.argv[2]]
else:
    upload_dir = os.path.join(os.getcwd(), "data", "uploads")
    try:
        files = [os.path.join(upload_dir, f) for f in os.listdir(upload_dir)]
        if not files:
            sys.stderr.write("Error: No files found in the uploads directory.\n")
            sys.exit(1)
    except FileNotFoundError:
        sys.stderr.write("Error: The specified upload directory does not exist.\n")
        sys.exit(1)

all_documents = []
for file_path in files:
    try:
        all_documents.extend(process_file(file_path))
    except Exception as e:
        sys.stderr.write(f"Error processing file {file_path}: {e}\n")

vector_retriever = build_vector_store(all_documents)
hybrid_retriever = HybridRetriever(vector_retriever, all_documents)
workflow = create_workflow(hybrid_retriever)

if __name__ == '__main__':
    if len(sys.argv) > 1:
        question = sys.argv[1]
    else:
        sys.stderr.write("Error: No question provided\n")
        sys.exit(1)

    if not question:
        sys.stderr.write("Error: Query must not be empty.\n")
        sys.exit(1)

    try:
        final_state = workflow.invoke({"query": question})

        if final_state.get("citations"):
            output_parts = [
                f"\n--- Answer ---\n{final_state['answer']}",
                f"\n--- Citations ---\n{final_state['citations']}",
                f"\n--- Summary ---\n{final_state['utility_response']}"
            ]
            sys.stdout.write("".join(output_parts))
        else:
            sys.stdout.write(f"\n--- Clarification ---\n{final_state['answer']}")

    except Exception as e:
        sys.stderr.write(f"An error occurred during workflow invocation: {e}\n")
        sys.exit(1)
