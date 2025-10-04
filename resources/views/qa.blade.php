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
            display: flex;
            align-items: center;
            gap: 8px;
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

        .chat-info {
            flex: 1;
            min-width: 0;
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

        .chat-actions {
            display: flex;
            gap: 4px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .chat-item:hover .chat-actions {
            opacity: 1;
        }

        .chat-action-btn {
            background: transparent;
            border: none;
            color: inherit;
            cursor: pointer;
            padding: 4px;
            opacity: 0.6;
            transition: opacity 0.2s;
            font-size: 14px;
        }

        .chat-action-btn:hover {
            opacity: 1;
        }

        .chat-item.active .chat-action-btn {
            opacity: 0.7;
        }

        .chat-item.active .chat-action-btn:hover {
            opacity: 1;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 20px 30px;
            border-bottom: 1px solid #333;
            background: #111;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            min-width: 0;
        }

        .chat-header h1 {
            font-size: 20px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .rename-btn {
            background: transparent;
            border: none;
            color: #fff;
            cursor: pointer;
            padding: 6px;
            opacity: 0.5;
            transition: opacity 0.2s;
            font-size: 16px;
        }

        .rename-btn:hover {
            opacity: 1;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: #111;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 24px;
            width: 90%;
            max-width: 400px;
        }

        .modal-header {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .modal-input {
            width: 100%;
            padding: 12px;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 6px;
            color: #fff;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .modal-input:focus {
            outline: none;
            border-color: #fff;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .modal-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .modal-btn-cancel {
            background: #1a1a1a;
            color: #fff;
            border: 1px solid #333;
        }

        .modal-btn-cancel:hover {
            background: #222;
        }

        .modal-btn-save {
            background: #fff;
            color: #000;
        }

        .modal-btn-save:hover {
            background: #e0e0e0;
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
            line-height: 1.8;
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

        .answer p {
            margin-bottom: 12px;
        }

        .answer p:last-child {
            margin-bottom: 0;
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

        .file-tag-remove {
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
            padding: 0;
            opacity: 0.6;
            font-size: 14px;
        }

        .file-tag-remove:hover {
            opacity: 1;
        }

        .message-actions {
            margin-top: 8px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .message:hover .message-actions {
            opacity: 1;
        }

        .message-action-btn {
            background: transparent;
            border: 1px solid #333;
            color: #fff;
            cursor: pointer;
            padding: 4px 10px;
            font-size: 11px;
            border-radius: 4px;
            margin-right: 6px;
            transition: all 0.2s;
        }

        .message-action-btn:hover {
            background: #222;
            border-color: #555;
        }

        .question .message-action-btn {
            color: #fff;
            border-color: #444;
        }

        .question .message-action-btn:hover {
            background: #333;
            border-color: #666;
        }

        .edit-mode {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .edit-input {
            flex: 1;
            padding: 10px;
            background: #222;
            border: 1px solid #555;
            border-radius: 6px;
            color: #fff;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            min-height: 60px;
        }

        .edit-input:focus {
            outline: none;
            border-color: #fff;
        }

        .edit-actions {
            display: flex;
            gap: 6px;
            align-items: flex-start;
        }

        .edit-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .edit-btn-save {
            background: #fff;
            color: #000;
        }

        .edit-btn-save:hover {
            background: #e0e0e0;
        }

        .edit-btn-cancel {
            background: #1a1a1a;
            color: #fff;
            border: 1px solid #333;
        }

        .edit-btn-cancel:hover {
            background: #222;
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
        <div class="sidebar">
            <div class="sidebar-header">
                <button class="new-chat-btn" onclick="createNewChat()">+ New Chat</button>
            </div>
            <div class="chats-list" id="chats-list"></div>
        </div>

        <div class="main-content">
            <div class="chat-header">
                <div class="chat-header-left">
                    <h1 id="chat-title">Select or create a chat</h1>
                    <button class="rename-btn" id="rename-btn" onclick="openRenameModal()" style="display: none;" title="Rename chat">✏️</button>
                </div>
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
                        <label class="file-input-label" for="file-input">📎 Upload File</label>
                        <input type="file" id="file-input" class="file-input" onchange="uploadFile()">
                    </div>
                    <div class="uploaded-files" id="uploaded-files"></div>
                </div>

                <div class="input-wrapper">
                    <textarea id="question-input" class="question-input" rows="3" placeholder="Ask a question about your documents..." onkeydown="handleKeyPress(event)"></textarea>
                    <button class="send-btn" onclick="askQuestion()">Send</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="rename-modal" onclick="if(event.target === this) closeRenameModal()">
        <div class="modal-content">
            <div class="modal-header">Rename Chat</div>
            <input type="text" id="rename-input" class="modal-input" placeholder="Enter chat name..." maxlength="100">
            <div class="modal-actions">
                <button class="modal-btn modal-btn-cancel" onclick="closeRenameModal()">Cancel</button>
                <button class="modal-btn modal-btn-save" onclick="saveChatTitle()">Save</button>
            </div>
        </div>
    </div>

    <script>
        let currentChatId = null;
        let pollingInterval = null;

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
                    <div class="chat-info">
                        <div class="chat-title">${escapeHtml(chat.title || 'New Chat')}</div>
                        <div class="chat-date">${formatDate(chat.updated_at)}</div>
                    </div>
                    <div class="chat-actions">
                        <button class="chat-action-btn" onclick="event.stopPropagation(); openRenameModalForChat(${chat.id}, '${escapeHtml(chat.title)}')" title="Rename">✏️</button>
                        <button class="chat-action-btn" onclick="event.stopPropagation(); deleteChat(${chat.id})" title="Delete">🗑️</button>
                    </div>
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
                document.getElementById('rename-btn').style.display = 'block';
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
                    let cleanAnswer = msg.answer.replace(/\s*\[.*?\]\s*/g, ' ').replace(/\s+/g, ' ').trim();

                    answerHtml = `
                        <div class="message">
                            <div class="message-label">Assistant</div>
                            <div class="message-content answer">
                                <div>${cleanAnswer.replace(/\n\n/g, '</div><div style="margin-top: 12px;">').replace(/\n/g, '<br>')}</div>
                                ${msg.citations && msg.citations.length > 0 ? `
                                    <div class="sources-section">
                                        <div class="sources-title">📚 Sources</div>
                                        <div>
                                            ${msg.citations.map(c => `<span class="source-item"><span class="source-icon">📄</span><span>${c}</span></span>`).join('')}
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                } else {
                    answerHtml = '<div class="message"><div class="message-content answer loading">Processing your question...</div></div>';
                }

                return `
                    <div class="message" id="message-${msg.id}">
                        <div class="message-label">You</div>
                        <div class="message-content question">
                            <div class="question-text" id="question-text-${msg.id}">${escapeHtml(msg.question)}</div>
                            <div class="message-actions">
                                <button class="message-action-btn" onclick="editMessage(${msg.id}, '${escapeHtml(msg.question).replace(/'/g, "\\'")}')">✏️ Edit</button>
                            </div>
                        </div>
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
                    📄 ${escapeHtml(f.original_name)}
                    <button class="file-tag-remove" onclick="deleteFile(${f.id})" title="Remove file">✕</button>
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
                        document.getElementById('rename-btn').style.display = 'none';
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

        function openRenameModal() {
            if (!currentChatId) return;

            const currentTitle = document.getElementById('chat-title').textContent;
            document.getElementById('rename-input').value = currentTitle;
            document.getElementById('rename-modal').classList.add('active');
            document.getElementById('rename-input').focus();
        }

        function openRenameModalForChat(chatId, title) {
            selectChat(chatId);
            setTimeout(() => {
                document.getElementById('rename-input').value = title;
                document.getElementById('rename-modal').classList.add('active');
                document.getElementById('rename-input').focus();
            }, 100);
        }

        function closeRenameModal() {
            document.getElementById('rename-modal').classList.remove('active');
            document.getElementById('rename-input').value = '';
        }

        async function saveChatTitle() {
            if (!currentChatId) return;

            const newTitle = document.getElementById('rename-input').value.trim();

            if (!newTitle) {
                alert('Please enter a chat name');
                return;
            }

            try {
                const response = await fetch(`/api/chats/${currentChatId}/title`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ title: newTitle })
                });

                if (response.ok) {
                    document.getElementById('chat-title').textContent = newTitle;
                    closeRenameModal();
                    await loadChats();
                } else {
                    alert('Error updating chat title');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error updating chat title');
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
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

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeRenameModal();
            }
        });

        document.getElementById('rename-input').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                saveChatTitle();
            }
        });

        async function deleteFile(fileId) {
            if (!currentChatId) return;

            if (!confirm('Are you sure you want to delete this file?')) {
                return;
            }

            try {
                const response = await fetch(`/api/chats/${currentChatId}/files/${fileId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    await selectChat(currentChatId);
                } else {
                    alert('Error deleting file');
                }
            } catch (error) {
                console.error('Error deleting file:', error);
                alert('Error deleting file');
            }
        }

        function editMessage(messageId, currentQuestion) {
            const messageEl = document.getElementById(`message-${messageId}`);
            const questionTextEl = document.getElementById(`question-text-${messageId}`);

            const editHtml = `
                <div class="edit-mode">
                    <textarea class="edit-input" id="edit-input-${messageId}">${currentQuestion}</textarea>
                    <div class="edit-actions">
                        <button class="edit-btn edit-btn-save" onclick="saveEditedMessage(${messageId})">Save</button>
                        <button class="edit-btn edit-btn-cancel" onclick="cancelEditMessage(${messageId}, '${currentQuestion.replace(/'/g, "\\'")}')">Cancel</button>
                    </div>
                </div>
            `;

            questionTextEl.parentElement.innerHTML = editHtml;
            document.getElementById(`edit-input-${messageId}`).focus();
        }

        function cancelEditMessage(messageId, originalQuestion) {
            const messageEl = document.getElementById(`message-${messageId}`);
            const questionContent = messageEl.querySelector('.question');

            questionContent.innerHTML = `
                <div class="question-text" id="question-text-${messageId}">${escapeHtml(originalQuestion)}</div>
                <div class="message-actions">
                    <button class="message-action-btn" onclick="editMessage(${messageId}, '${originalQuestion.replace(/'/g, "\\'")}')">✏️ Edit</button>
                </div>
            `;
        }

        async function saveEditedMessage(messageId) {
            if (!currentChatId) return;

            const editedQuestion = document.getElementById(`edit-input-${messageId}`).value.trim();

            if (!editedQuestion) {
                alert('Question cannot be empty');
                return;
            }

            try {
                const response = await fetch(`/api/chats/${currentChatId}/messages/${messageId}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ question: editedQuestion })
                });

                const data = await response.json();

                if (data.query_id) {
                    await selectChat(currentChatId);
                    pollForAnswer(data.query_id);
                } else {
                    alert('Error updating message');
                }
            } catch (error) {
                console.error('Error updating message:', error);
                alert('Error updating message');
            }
        }
    </script>
</body>
</html>
