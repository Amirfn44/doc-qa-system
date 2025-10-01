<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doc Q&A</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Ask a question</h1>
        <form id="qa-form">
            <div class="mb-3">
                <label for="question" class="form-label">Your Question</label>
                <textarea class="form-control" id="question" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Ask</button>
        </form>

        <div id="answer" class="mt-5">
            <h2>Answer</h2>
            <div id="answer-content"></div>
        </div>
    </div>

    <script>
        document.getElementById('qa-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const question = document.getElementById('question').value;
            const answerContent = document.getElementById('answer-content');
            answerContent.innerHTML = 'Sending question...';

            fetch('/api/ask', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ question: question })
            })
            .then(response => response.json())
            .then(data => {
                const queryId = data.query_id;
                if (queryId) {
                    answerContent.innerHTML = 'Processing question...';
                    pollForAnswer(queryId, answerContent);
                } else {
                    answerContent.innerHTML = 'An error occurred: ' + data.error;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                answerContent.innerHTML = 'An error occurred during submission.';
            });
        });

        function pollForAnswer(queryId, answerContent) {
            const interval = setInterval(() => {
                fetch(`/api/check-status?query_id=${queryId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'processing') {
                        } else if (data.status === 'error') {
                            clearInterval(interval);
                            answerContent.innerHTML = `<div class="alert alert-danger">Error: ${data.details}<br><pre>${data.error_output || ''}</pre></div>`;
                        } else if (data.status === 'completed') {
                            clearInterval(interval);
                            renderAnswer(data, answerContent);
                        }
                    })
                    .catch(error => {
                        clearInterval(interval);
                        console.error('Polling error:', error);
                        answerContent.innerHTML = '<div class="alert alert-danger">An error occurred while polling.</div>';
                    });
            }, 5000);
        }

        function renderAnswer(data, answerContent) {
            const output = data.output;
            let html = '';

            if (output) {
                if (output.includes('--- Citations ---')) {
                    const parts = output.split('---');
                    html = '';
                    for (let i = 1; i < parts.length; i += 2) {
                        const title = parts[i].trim().replace(/\n/g, '');
                        const content = parts[i+1].trim();
                        html += `<h3>${title}</h3><p>${content.replace(/\n/g, '<br>')}</p>`;
                    }
                } else {
                    html = `<h3>Clarification</h3><p>${output.replace(/\n/g, '<br>')}</p>`;
                }
                answerContent.innerHTML = html;
            } else {
                answerContent.innerHTML = '<div class="alert alert-warning">An unexpected response was received.</div>';
            }
        }
    </script>
</body>
</html>
