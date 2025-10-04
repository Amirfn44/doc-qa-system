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

if __name__ == '__main__':
    if len(sys.argv) < 2:
        sys.stderr.write("Error: No question provided\n")
        sys.exit(1)

    question = sys.argv[1]

    if len(sys.argv) > 2:
        chat_id = sys.argv[2]
        upload_dir = os.path.join(os.getcwd(), "data", "uploads", str(chat_id))
        db_location = os.path.join(os.getcwd(), "db", "chroma", str(chat_id))
    else:
        upload_dir = os.path.join(os.getcwd(), "data", "uploads")
        db_location = os.path.join(os.getcwd(), "db", "chroma")

    if not os.path.exists(upload_dir):
        sys.stderr.write(f"Error: Upload directory does not exist: {upload_dir}\n")
        sys.exit(1)

    try:
        files = [
            os.path.join(upload_dir, f)
            for f in os.listdir(upload_dir)
            if os.path.isfile(os.path.join(upload_dir, f))
        ]

        if not files:
            sys.stderr.write("Error: No files found in the uploads directory.\n")
            sys.exit(1)
    except Exception as e:
        sys.stderr.write(f"Error reading upload directory: {e}\n")
        sys.exit(1)

    all_documents = []
    for file_path in files:
        try:
            docs = process_file(file_path)
            all_documents.extend(docs)
            sys.stderr.write(f"Processed file: {file_path} ({len(docs)} chunks)\n")
        except Exception as e:
            sys.stderr.write(f"Error processing file {file_path}: {e}\n")

    if not all_documents:
        sys.stderr.write("Error: No documents were successfully processed.\n")
        sys.exit(1)

    sys.stderr.write(f"Total documents processed: {len(all_documents)}\n")

    try:
        vector_retriever = build_vector_store(all_documents, db_location=db_location)
        sys.stderr.write(f"Vector store built at: {db_location}\n")
    except Exception as e:
        sys.stderr.write(f"Error building vector store: {e}\n")
        sys.exit(1)

    try:
        hybrid_retriever = HybridRetriever(vector_retriever, all_documents)
        sys.stderr.write("Hybrid retriever created\n")
    except Exception as e:
        sys.stderr.write(f"Error creating hybrid retriever: {e}\n")
        sys.exit(1)

    try:
        workflow = create_workflow(hybrid_retriever)
        sys.stderr.write("Workflow created\n")
    except Exception as e:
        sys.stderr.write(f"Error creating workflow: {e}\n")
        sys.exit(1)

    if not question:
        sys.stderr.write("Error: Query must not be empty.\n")
        sys.exit(1)

    try:
        sys.stderr.write(f"Invoking workflow with question: {question}\n")
        final_state = workflow.invoke({"query": question})
        sys.stderr.write(f"Workflow completed\n")

        # Format output
        if final_state.get("citations"):
            output_parts = [
                f"--- Answer ---\n{final_state['answer']}",
                f"\n--- Citations ---\n{final_state['citations']}",
                f"\n--- Summary ---\n{final_state['utility_response']}"
            ]
            sys.stdout.write("".join(output_parts))
            sys.stdout.flush()
        else:
            sys.stdout.write(f"--- Clarification ---\n{final_state['answer']}")
            sys.stdout.flush()

    except Exception as e:
        sys.stderr.write(f"An error occurred during workflow invocation: {e}\n")
        import traceback
        sys.stderr.write(traceback.format_exc())
        sys.exit(1)
