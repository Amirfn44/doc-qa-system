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
            scroll-behavior: smooth;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #000;
            color: #fff;
            height: 100vh;
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            display: flex;
            height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 300px;
            background: linear-gradient(180deg, #111 0%, #0a0a0a 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            flex-direction: column;
            animation: slideInLeft 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .new-chat-btn {
            width: 100%;
            padding: 14px 20px;
            background: linear-gradient(135deg, #fff 0%, #f0f0f0 100%);
            color: #000;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.3px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .new-chat-btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }

        .new-chat-btn:hover:before {
            left: 100%;
        }

        .new-chat-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(255, 255, 255, 0.2);
        }

        .new-chat-btn:active {
            transform: translateY(-1px);
        }

        .chats-list {
            flex: 1;
            overflow-y: auto;
            padding: 12px;
        }

        .chat-item {
            padding: 14px 16px;
            margin-bottom: 8px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeIn 0.4s ease-out;
        }

        .chat-item:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.12);
            transform: translateX(6px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .chat-item.active {
            background: linear-gradient(135deg, #fff 0%, #f5f5f5 100%);
            color: #000;
            border-color: transparent;
            box-shadow: 0 6px 25px rgba(255, 255, 255, 0.2);
            transform: translateX(6px);
        }

        .chat-info {
            flex: 1;
            min-width: 0;
        }

        .chat-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chat-date {
            font-size: 11px;
            opacity: 0.6;
            font-weight: 500;
        }

        .chat-actions {
            display: flex;
            gap: 6px;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .chat-item:hover .chat-actions {
            opacity: 1;
        }

        .chat-action-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: inherit;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 14px;
        }

        .chat-action-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.15) rotate(5deg);
        }

        .chat-item.active .chat-action-btn {
            background: rgba(0, 0, 0, 0.1);
        }

        .chat-item.active .chat-action-btn:hover {
            background: rgba(0, 0, 0, 0.2);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            animation: slideInRight 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .chat-header {
            padding: 24px 32px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: linear-gradient(180deg, #111 0%, #0d0d0d 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-header-left {
            display: flex;
            align-items: center;
            gap: 14px;
            flex: 1;
            min-width: 0;
        }

        .chat-header h1 {
            font-size: 22px;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            letter-spacing: -0.5px;
        }

        .rename-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            cursor: pointer;
            padding: 10px;
            border-radius: 8px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 16px;
        }

        .rename-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(15deg) scale(1.15);
            border-color: rgba(255, 255, 255, 0.2);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.88);
            backdrop-filter: blur(12px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease-out;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: linear-gradient(180deg, #1a1a1a 0%, #141414 100%);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 18px;
            padding: 32px;
            width: 90%;
            max-width: 450px;
            animation: scaleIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.6);
        }

        .modal-header {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 24px;
            letter-spacing: -0.3px;
        }

        .modal-input {
            width: 100%;
            padding: 16px 18px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #fff;
            font-size: 15px;
            margin-bottom: 28px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .modal-input:focus {
            outline: none;
            border-color: #fff;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.08);
        }

        .modal-actions {
            display: flex;
            gap: 14px;
            justify-content: flex-end;
        }

        .modal-btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            letter-spacing: 0.3px;
        }

        .modal-btn-cancel {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .modal-btn-cancel:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-3px);
        }

        .modal-btn-save {
            background: linear-gradient(135deg, #fff 0%, #f0f0f0 100%);
            color: #000;
            box-shadow: 0 6px 20px rgba(255, 255, 255, 0.15);
        }

        .modal-btn-save:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(255, 255, 255, 0.25);
        }

        /* Messages Container */
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 32px;
        }

        .message {
            margin-bottom: 32px;
            max-width: 850px;
            animation: fadeIn 0.5s ease-out;
        }

        .message-label {
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 12px;
            opacity: 0.6;
            text-transform: uppercase;
            letter-spacing: 1.2px;
        }

        .message-content {
            padding: 20px 24px;
            border-radius: 14px;
            line-height: 1.8;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 15px;
        }

        .message:hover .message-content {
            transform: translateX(6px);
        }

        .question {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .answer {
            background: linear-gradient(135deg, #fff 0%, #fafafa 100%);
            color: #000;
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.12);
        }

        .answer p {
            margin-bottom: 16px;
            line-height: 1.85;
        }

        .answer p:last-child {
            margin-bottom: 0;
        }

        .sources-section {
            margin-top: 26px;
            padding-top: 22px;
            border-top: 2px solid rgba(0, 0, 0, 0.08);
            animation: fadeIn 0.6s ease-out 0.3s both;
        }

        .sources-title {
            font-size: 11px;
            font-weight: 800;
            opacity: 0.7;
            margin-bottom: 14px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
        }

        .source-item {
            display: inline-flex;
            align-items: center;
            padding: 10px 16px;
            background: linear-gradient(135deg, #f8f8f8 0%, #ececec 100%);
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            margin-right: 12px;
            margin-bottom: 12px;
            gap: 10px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0, 0, 0, 0.06);
        }

        .source-item:hover {
            background: linear-gradient(135deg, #e8e8e8 0%, #dcdcdc 100%);
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.18);
        }

        .source-icon {
            opacity: 0.75;
            transition: transform 0.3s ease;
        }

        .source-item:hover .source-icon {
            transform: scale(1.25);
        }

        /* File Viewer Modal */
        .file-viewer-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.93);
            backdrop-filter: blur(15px);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease-out;
        }

        .file-viewer-modal.active {
            display: flex;
        }

        .file-viewer-content {
            background: #fff;
            width: 92%;
            max-width: 950px;
            height: 88vh;
            border-radius: 18px;
            display: flex;
            flex-direction: column;
            color: #000;
            animation: scaleIn 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 35px 90px rgba(0, 0, 0, 0.7);
        }

        .file-viewer-header {
            padding: 26px 32px;
            border-bottom: 2px solid rgba(0, 0, 0, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(180deg, #fafafa 0%, #f5f5f5 100%);
            border-radius: 18px 18px 0 0;
        }

        .file-viewer-title {
            font-size: 19px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 14px;
            letter-spacing: -0.3px;
        }

        .file-viewer-close {
            background: rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(0, 0, 0, 0.06);
            font-size: 24px;
            cursor: pointer;
            color: #666;
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .file-viewer-close:hover {
            background: rgba(0, 0, 0, 0.08);
            color: #000;
            transform: rotate(90deg) scale(1.1);
        }

        .file-viewer-body {
            flex: 1;
            overflow-y: auto;
            padding: 30px;
        }

        .file-content {
            font-family: 'SF Mono', 'Monaco', 'Courier New', monospace;
            font-size: 14px;
            line-height: 2;
            white-space: pre-wrap;
            word-wrap: break-word;
            animation: fadeIn 0.6s ease-out;
        }

        .highlight {
            background: linear-gradient(120deg, #fff9c4 0%, #ffeb3b 100%);
            padding: 4px 7px;
            border-radius: 5px;
            font-weight: 700;
            animation: fadeIn 0.4s ease-out;
            box-shadow: 0 2px 6px rgba(255, 235, 59, 0.4);
        }

        .search-info {
            padding: 16px 22px;
            background: linear-gradient(135deg, #f5f5f5 0%, #ececec 100%);
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 600;
            color: #555;
            border-left: 5px solid #ffeb3b;
            animation: slideInRight 0.5s ease-out;
        }

        .loading {
            opacity: 0.7;
            font-style: italic;
            animation: pulse 1.8s ease-in-out infinite;
        }

        /* Input Area */
        .input-area {
            padding: 16px 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            background: linear-gradient(180deg, #0d0d0d 0%, #111 100%);
        }

        .file-upload-area {
            margin-bottom: 12px;
        }

        .file-input-label {
            display: inline-block;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .file-input-label:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
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
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.4s ease;
            animation: fadeIn 0.4s ease-out;
        }

        .file-tag:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .file-tag-remove {
            background: rgba(255, 0, 0, 0.1);
            border: none;
            color: #ff6b6b;
            cursor: pointer;
            padding: 4px 6px;
            font-size: 12px;
            border-radius: 4px;
            transition: all 0.3s ease;
            font-weight: 700;
        }

        .file-tag-remove:hover {
            background: rgba(255, 0, 0, 0.2);
            transform: rotate(90deg) scale(1.2);
        }

        .message-actions {
            margin-top: 10px;
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .message:hover .message-actions {
            opacity: 1;
        }

        .message-action-btn {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
            cursor: pointer;
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 6px;
            margin-right: 8px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .message-action-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .question .message-action-btn {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .edit-mode {
            display: flex;
            gap: 10px;
            margin-top: 12px;
            animation: fadeIn 0.4s ease-out;
        }

        .edit-input {
            flex: 1;
            padding: 10px 12px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            color: #fff;
            font-size: 13px;
            font-family: inherit;
            resize: vertical;
            min-height: 60px;
            transition: all 0.3s ease;
        }

        .edit-input:focus {
            outline: none;
            border-color: #fff;
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.08);
        }

        .edit-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .edit-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            min-width: 70px;
            letter-spacing: 0.3px;
        }

        .edit-btn-save {
            background: linear-gradient(135deg, #fff 0%, #f0f0f0 100%);
            color: #000;
            box-shadow: 0 3px 12px rgba(255, 255, 255, 0.15);
        }

        .edit-btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 255, 255, 0.25);
        }

        .edit-btn-cancel {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .edit-btn-cancel:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .input-wrapper {
            display: flex;
            gap: 12px;
            align-items: flex-end;
        }

        .question-input {
            flex: 1;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #fff;
            font-size: 13px;
            resize: none;
            font-family: inherit;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            line-height: 1.5;
            font-weight: 500;
        }

        .question-input:focus {
            outline: none;
            border-color: #fff;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.08);
        }

        .send-btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #fff 0%, #f0f0f0 100%);
            color: #000;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 800;
            font-size: 13px;
            letter-spacing: 0.5px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.15);
        }

        .send-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(255, 255, 255, 0.25);
        }

        .send-btn:active {
            transform: translateY(-1px);
        }

        .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .empty-state {
            text-align: center;
            padding: 100px 30px;
            opacity: 0.5;
            animation: fadeIn 0.8s ease-out;
        }

        .empty-state h2 {
            font-size: 32px;
            margin-bottom: 16px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .empty-state p {
            font-size: 16px;
            opacity: 0.8;
            font-weight: 500;
        }

        ::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.02);
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.12) 0%, rgba(255, 255, 255, 0.08) 100%);
            border-radius: 6px;
            border: 2px solid rgba(0, 0, 0, 0.1);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.18) 0%, rgba(255, 255, 255, 0.12) 100%);
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

    <div class="file-viewer-modal" id="file-viewer-modal" onclick="if(event.target === this) closeFileViewer()">
        <div class="file-viewer-content">
            <div class="file-viewer-header">
                <div class="file-viewer-title">
                    <span>📄</span>
                    <span id="file-viewer-filename">Document</span>
                </div>
                <button class="file-viewer-close" onclick="closeFileViewer()">✕</button>
            </div>
            <div class="file-viewer-body">
                <div class="search-info" id="search-info"></div>
                <div class="file-content" id="file-content"></div>
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
                    let paragraphs = cleanAnswer.split(/\n\n+/);
                    let formattedAnswer = paragraphs.map(p => `<p>${p.replace(/\n/g, '<br>')}</p>`).join('');

                    answerHtml = `
                        <div class="message">
                            <div class="message-label">Assistant</div>
                            <div class="message-content answer">
                                ${formattedAnswer}
                                ${msg.citations && msg.citations.length > 0 ? `
                                    <div class="sources-section">
                                        <div class="sources-title">📚 Sources</div>
                                        <div>
                                            ${msg.citations.map(c => `
                                                <span class="source-item" onclick="openFileViewer('${escapeHtml(c)}', ${msg.id})" title="Click to view document">
                                                    <span class="source-icon">📄</span>
                                                    <span>${escapeHtml(c)}</span>
                                                </span>
                                            `).join('')}
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
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
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
            if (pollingInterval) clearInterval(pollingInterval);

            pollingInterval = setInterval(async () => {
                try {
                    const response = await fetch(`/api/check-status?query_id=${queryId}`);
                    const data = await response.json();

                    if (data.status === 'error') {
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
            if (!confirm('Are you sure you want to delete this chat?')) return;

            try {
                const response = await fetch(`/api/chats/${chatId}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
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
            if (e.key === 'Escape') closeRenameModal();
        });

        document.getElementById('rename-input').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') saveChatTitle();
        });

        async function deleteFile(fileId) {
            if (!currentChatId || !confirm('Are you sure you want to delete this file?')) return;

            try {
                const response = await fetch(`/api/chats/${currentChatId}/files/${fileId}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
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

        async function openFileViewer(filename, messageId) {
            if (!currentChatId) return;

            try {
                const response = await fetch(`/api/chats/${currentChatId}/files/content?filename=${encodeURIComponent(filename)}`);
                if (!response.ok) {
                    alert('Error loading file');
                    return;
                }

                const data = await response.json();
                const chatResponse = await fetch(`/api/chats/${currentChatId}`);
                const chat = await chatResponse.json();
                const message = chat.messages.find(m => m.id === messageId);

                document.getElementById('file-viewer-filename').textContent = filename;
                document.getElementById('file-viewer-modal').classList.add('active');
                displayFileContent(data.content, message ? message.answer : '');
            } catch (error) {
                console.error('Error opening file:', error);
                alert('Error loading file');
            }
        }

        function displayFileContent(content, answerText) {
            const fileContentEl = document.getElementById('file-content');
            const searchInfoEl = document.getElementById('search-info');

            const words = answerText.toLowerCase().replace(/[^\w\s]/g, ' ').split(/\s+/).filter(w => w.length >= 4);
            const uniqueWords = [...new Set(words)];

            let highlightedContent = content;
            let highlightCount = 0;

            uniqueWords.forEach(word => {
                const regex = new RegExp(`\\b(${word}\\w*)\\b`, 'gi');
                const matches = content.match(regex);
                if (matches && matches.length > 0) {
                    highlightCount += matches.length;
                    highlightedContent = highlightedContent.replace(regex, '<span class="highlight">$1</span>');
                }
            });

            if (highlightCount > 0) {
                searchInfoEl.textContent = `Found ${highlightCount} highlighted term${highlightCount > 1 ? 's' : ''} related to the answer`;
                searchInfoEl.style.display = 'block';
            } else {
                searchInfoEl.style.display = 'none';
            }

            fileContentEl.innerHTML = highlightedContent;
        }

        function closeFileViewer() {
            document.getElementById('file-viewer-modal').classList.remove('active');
            document.getElementById('file-content').innerHTML = '';
        }
    </script>
</body>
</html>
