<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Doc Q&A - Multi Chat</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #000;
            color: #fff;
            height: 100vh;
            overflow: hidden;
        }

        .container {
            display: flex;
            height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: #111;
            border-right: 1px solid #333;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #333;
        }

        .new-chat-btn {
            width: 100%;
            padding: 12px 20px;
            background: #fff;
            color: #000;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
        }

        .new-chat-btn:hover {
            background: #e0e0e0;
        }

        .chats-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }

        .chat-item {
            padding: 12px 16px;
            margin-bottom: 6px;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }

        .chat-item:hover {
            background: #222;
            border-color: #555;
        }

        .chat-item.active {
            background: #fff;
            color: #000;
            border-color: #fff;
        }

        .chat-title {
            font-weight: 500;
            font-size: 14px;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chat-date {
            font-size: 11px;
            opacity: 0.6;
        }

        .delete-chat {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: inherit;
            cursor: pointer;
            padding: 4px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .chat-item:hover .delete-chat {
            opacity: 0.6;
        }

        .delete-chat:hover {
            opacity: 1 !important;
        }

        /* Main content */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 20px 30px;
            border-bottom: 1px solid #333;
            background: #111;
        }

        .chat-header h1 {
            font-size: 20px;
            font-weight: 600;
        }

        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 30px;
        }

        .message {
            margin-bottom: 30px;
            max-width: 800px;
        }

        .message-label {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 8px;
            opacity: 0.7;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .message-content {
            padding: 16px 20px;
            border-radius: 8px;
            line-height: 1.6;
        }

        .question {
            background: #1a1a1a;
            border: 1px solid #333;
        }

        .answer {
            background: #fff;
            color: #000;
            border: 1px solid #fff;
        }

        .sources-section {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #e0e0e0;
        }

        .sources-title {
            font-size: 12px;
            font-weight: 600;
            opacity: 0.7;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .source-item {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            background: #f5f5f5;
            border-radius: 6px;
            font-size: 12px;
            margin-right: 8px;
            margin-bottom: 8px;
            gap: 6px;
        }

        .source-icon {
            opacity: 0.6;
        }

        .loading {
            opacity: 0.6;
            font-style: italic;
        }

        /* Input area */
        .input-area {
            padding: 20px 30px;
            border-top: 1px solid #333;
            background: #111;
        }

        .file-upload-area {
            margin-bottom: 15px;
        }

        .file-input-wrapper {
            display: inline-block;
        }

        .file-input-label {
            display: inline-block;
            padding: 8px 16px;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        }

        .file-input-label:hover {
            background: #222;
            border-color: #555;
        }

        .file-input {
            display: none;
        }

        .uploaded-files {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .file-tag {
            padding: 6px 12px;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 4px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .file-tag button {
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
            padding: 0;
            opacity: 0.6;
        }

        .file-tag button:hover {
            opacity: 1;
        }

        .input-wrapper {
            display: flex;
            gap: 12px;
        }

        .question-input {
            flex: 1;
            padding: 14px 18px;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            resize: none;
            font-family: inherit;
            transition: all 0.2s;
        }

        .question-input:focus {
            outline: none;
            border-color: #fff;
            background: #222;
        }

        .send-btn {
            padding: 14px 28px;
            background: #fff;
            color: #000;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
        }

        .send-btn:hover {
            background: #e0e0e0;
        }

        .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            opacity: 0.5;
        }

        .empty-state h2 {
            font-size: 24px;
            margin-bottom: 12px;
        }

        .empty-state p {
            font-size: 14px;
        }

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #111;
        }

        ::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <button class="new-chat-btn" onclick="createNewChat()">+ New Chat</button>
            </div>
            <div class="chats-list" id="chats-list">
                <!-- Chats will be loaded here -->
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="chat-header">
                <h1 id="chat-title">Select or create a chat</h1>
            </div>

            <div class="messages-container" id="messages-container">
                <div class="empty-state">
                    <h2>Welcome to Doc Q&A</h2>
                    <p>Create a new chat or select an existing one to get started</p>
                </div>
            </div>

            <div class="input-area" id="input-area" style="display: none;">
                <div class="file-upload-area">
                    <div class="file-input-wrapper">
                        <label class="file-input-label" for="file-input">
                            📎 Upload File
                        </label>
                        <input type="file" id="file-input" class="file-input" onchange="uploadFile()">
                    </div>
                    <div class="uploaded-files" id="uploaded-files"></div>
                </div>

                <div class="input-wrapper">
                    <textarea
                        id="question-input"
                        class="question-input"
                        rows="3"
                        placeholder="Ask a question about your documents..."
                        onkeydown="handleKeyPress(event)"
                    ></textarea>
                    <button class="send-btn" onclick="askQuestion()">Send</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentChatId = null;
        let pollingInterval = null;

        // Load chats on page load
        window.addEventListener('DOMContentLoaded', loadChats);

        async function loadChats() {
            try {
                const response = await fetch('/api/chats');
                const chats = await response.json();
                renderChats(chats);
            } catch (error) {
                console.error('Error loading chats:', error);
            }
        }

        function renderChats(chats) {
            const chatsList = document.getElementById('chats-list');

            if (chats.length === 0) {
                chatsList.innerHTML = '<div style="padding: 20px; text-align: center; opacity: 0.5; font-size: 13px;">No chats yet</div>';
                return;
            }

            chatsList.innerHTML = chats.map(chat => `
                <div class="chat-item ${chat.id === currentChatId ? 'active' : ''}" onclick="selectChat(${chat.id})">
                    <div class="chat-title">${chat.title || 'New Chat'}</div>
                    <div class="chat-date">${formatDate(chat.updated_at)}</div>
                    <button class="delete-chat" onclick="event.stopPropagation(); deleteChat(${chat.id})">🗑️</button>
                </div>
            `).join('');
        }

        async function createNewChat() {
            try {
                const response = await fetch('/api/chats', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ title: 'New Chat' })
                });
                const chat = await response.json();
                await loadChats();
                selectChat(chat.chat_id);
            } catch (error) {
                console.error('Error creating chat:', error);
            }
        }

        async function selectChat(chatId) {
            currentChatId = chatId;

            try {
                const response = await fetch(`/api/chats/${chatId}`);
                const chat = await response.json();

                document.getElementById('chat-title').textContent = chat.title || 'Chat';
                document.getElementById('input-area').style.display = 'block';

                renderMessages(chat.messages);
                renderUploadedFiles(chat.files);
                await loadChats();
            } catch (error) {
                console.error('Error loading chat:', error);
            }
        }

        function renderMessages(messages) {
            const container = document.getElementById('messages-container');

            if (messages.length === 0) {
                container.innerHTML = '<div class="empty-state"><h2>No messages yet</h2><p>Ask a question to get started</p></div>';
                return;
            }

            container.innerHTML = messages.map(msg => {
                let answerHtml = '';

                if (msg.answer) {
                    // Clean up the answer text
                    let cleanAnswer = msg.answer
                        .replace(/\[.*?\]/g, '') // Remove citation markers like [file.txt]
                        .trim();

                    answerHtml = `
                        <div class="message">
                            <div class="message-label">Assistant</div>
                            <div class="message-content answer">
                                ${cleanAnswer.replace(/\n/g, '<br>')}
                                ${msg.citations && msg.citations.length > 0 ? `
                                    <div class="citations">
                                        <div class="citations-label">Sources:</div>
                                        ${msg.citations.map(c => `<span class="citation-item">${c}</span>`).join('')}
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                } else {
                    answerHtml = '<div class="message"><div class="message-content answer loading">Processing...</div></div>';
                }

                return `
                    <div class="message">
                        <div class="message-label">You</div>
                        <div class="message-content question">${msg.question}</div>
                    </div>
                    ${answerHtml}
                `;
            }).join('');

            container.scrollTop = container.scrollHeight;
        }

        function renderUploadedFiles(files) {
            const container = document.getElementById('uploaded-files');
            container.innerHTML = files.map(f => `
                <div class="file-tag">
                    📄 ${f.original_name}
                </div>
            `).join('');
        }

        async function uploadFile() {
            if (!currentChatId) {
                alert('Please select a chat first');
                return;
            }

            const fileInput = document.getElementById('file-input');
            const file = fileInput.files[0];

            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            try {
                const response = await fetch(`/api/chats/${currentChatId}/upload`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });

                if (response.ok) {
                    fileInput.value = '';
                    selectChat(currentChatId);
                } else {
                    alert('Error uploading file');
                }
            } catch (error) {
                console.error('Error uploading file:', error);
                alert('Error uploading file');
            }
        }

        async function askQuestion() {
            if (!currentChatId) {
                alert('Please select a chat first');
                return;
            }

            const questionInput = document.getElementById('question-input');
            const question = questionInput.value.trim();

            if (!question) {
                alert('Please enter a question');
                return;
            }

            questionInput.value = '';
            questionInput.disabled = true;

            try {
                const response = await fetch(`/api/chats/${currentChatId}/ask`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ question })
                });

                const data = await response.json();

                if (data.query_id) {
                    await selectChat(currentChatId);
                    pollForAnswer(data.query_id);
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                    questionInput.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred during submission.');
                questionInput.disabled = false;
            }
        }

        function pollForAnswer(queryId) {
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }

            pollingInterval = setInterval(async () => {
                try {
                    const response = await fetch(`/api/check-status?query_id=${queryId}`);
                    const data = await response.json();

                    if (data.status === 'processing') {
                        // Still processing
                    } else if (data.status === 'error') {
                        clearInterval(pollingInterval);
                        pollingInterval = null;
                        alert('Error: ' + data.details);
                        document.getElementById('question-input').disabled = false;
                        await selectChat(currentChatId);
                    } else if (data.status === 'completed') {
                        clearInterval(pollingInterval);
                        pollingInterval = null;
                        document.getElementById('question-input').disabled = false;
                        await selectChat(currentChatId);
                    }
                } catch (error) {
                    clearInterval(pollingInterval);
                    pollingInterval = null;
                    console.error('Polling error:', error);
                    document.getElementById('question-input').disabled = false;
                }
            }, 3000);
        }

        async function deleteChat(chatId) {
            if (!confirm('Are you sure you want to delete this chat?')) {
                return;
            }

            try {
                const response = await fetch(`/api/chats/${chatId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    if (currentChatId === chatId) {
                        currentChatId = null;
                        document.getElementById('chat-title').textContent = 'Select or create a chat';
                        document.getElementById('input-area').style.display = 'none';
                        document.getElementById('messages-container').innerHTML = '<div class="empty-state"><h2>Welcome to Doc Q&A</h2><p>Create a new chat or select an existing one to get started</p></div>';
                    }
                    await loadChats();
                } else {
                    alert('Error deleting chat');
                }
            } catch (error) {
                console.error('Error deleting chat:', error);
                alert('Error deleting chat');
            }
        }

        function handleKeyPress(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                askQuestion();
            }
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins}m ago`;
            if (diffHours < 24) return `${diffHours}h ago`;
            if (diffDays < 7) return `${diffDays}d ago`;

            return date.toLocaleDateString();
        }
    </script>
</body>
</html>
