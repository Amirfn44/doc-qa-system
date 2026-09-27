<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite('resources/js/app.js')
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

        .source-excerpt {
            display: block;
            max-width: 420px;
            margin-top: 5px;
            color: #666;
            font-size: 11px;
            font-weight: 400;
            line-height: 1.4;
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
        @media (max-width: 768px) {
            body { overflow: auto; }
            .container { min-height: 100vh; height: auto; flex-direction: column; }
            .sidebar { width: 100%; max-height: 180px; border-right: 0; border-bottom: 1px solid rgba(255,255,255,.08); }
            .sidebar-header { padding: 12px; }
            .chats-list { display: flex; gap: 8px; overflow-x: auto; padding: 8px 12px; }
            .chat-item { min-width: 180px; margin-bottom: 0; }
            .main-content { min-height: calc(100vh - 180px); }
            .chat-header { padding: 16px; }
            .messages-container { padding: 16px; }
            .message { max-width: 100%; margin-bottom: 20px; }
            .message-content { padding: 14px 16px; font-size: 14px; }
            .input-area { padding: 12px; }
            .source-item { max-width: 100%; margin-right: 0; }
            .source-excerpt { max-width: 260px; }
            .file-viewer-content { width: 96%; height: 82vh; }
        }

        /* Graphite workspace: mint accents, inset panels, consistent icon sizing. */
        :root {
            --ink: #eaf0ed;
            --muted: #a1b0ab;
            --panel: #111817;
            --panel-soft: #18211f;
            --line: #2a3833;
            --line-hover: #587568;
            --accent: #a6edd5;
            --accent-soft: #1d3830;
            --radius: 12px;
            --icon-size: 18px;
        }
        body {
            background: #090e0c;
            color: var(--ink);
            font-family: "Segoe UI", ui-sans-serif, system-ui, -apple-system, sans-serif;
            font-size: 14px;
            height: 100dvh;
            color-scheme: dark;
        }
        button, input, textarea { font-family: inherit; }
        button, label, .chat-item, .source-item { transition: background .18s ease, border-color .18s ease, color .18s ease; }
        button:focus-visible, textarea:focus-visible, input:focus-visible, [tabindex]:focus-visible {
            outline: 2px solid var(--accent); outline-offset: 3px;
        }
        [data-lucide] { width: var(--icon-size); height: var(--icon-size); flex-shrink: 0; vertical-align: middle; stroke-width: 1.8; }
        .container { height: 100dvh; padding: 12px; gap: 12px; }
        .sidebar, .main-content { background: var(--panel); border: 1px solid var(--line); border-radius: 16px; min-height: 0; animation: none; }
        .sidebar { width: 272px; flex-shrink: 0; box-shadow: none; }
        .sidebar-header { padding: 22px 16px 18px; border-bottom: 1px solid var(--line); }
        .workspace-brand { display: flex; align-items: center; gap: 11px; margin-bottom: 24px; }
        .brand-mark, .header-mark, .empty-state-mark { display: inline-flex; align-items: center; justify-content: center; color: var(--accent); background: var(--accent-soft); border: 1px solid #3b5e50; }
        .brand-mark { width: 38px; height: 38px; border-radius: 11px; --icon-size: 21px; }
        .brand-name { font-size: 17px; font-weight: 650; letter-spacing: -.5px; }
        .brand-caption { color: var(--muted); font-size: 11px; margin-top: 2px; }
        .new-chat-btn, .send-btn, .modal-btn-save, .edit-btn-save {
            background: var(--accent); color: #10271e; border: 1px solid #baf6e1; border-radius: var(--radius); box-shadow: inset 0 1px 0 #d7ffef; font-weight: 650; letter-spacing: 0;
        }
        .new-chat-btn { display: flex; justify-content: center; align-items: center; gap: 10px; min-height: 46px; padding: 11px 16px; font-size: 14px; --icon-size: 20px; }
        .new-chat-btn:before { display: none; }
        .new-chat-btn:hover, .send-btn:hover, .modal-btn-save:hover, .edit-btn-save:hover { background: #c1f6e4; box-shadow: none; transform: none; }
        .sidebar-section-label, .header-eyebrow { color: var(--muted); font-size: 10px; font-weight: 650; letter-spacing: 1.5px; text-transform: uppercase; }
        .sidebar-section-label { padding: 20px 18px 8px; }
        .chats-list { padding: 4px 10px 12px; min-height: 0; }
        .chat-item { padding: 10px; min-height: 64px; gap: 9px; margin-bottom: 6px; border: 1px solid transparent; border-radius: 10px; background: transparent; animation: none; }
        .chat-item > [data-lucide] { color: var(--muted); --icon-size: 19px; }
        .chat-item:hover, .chat-item.active { transform: none; box-shadow: none; color: var(--ink); background: var(--panel-soft); border-color: var(--line); }
        .chat-item.active { border-color: #426554; box-shadow: inset 3px 0 0 var(--accent); }
        .chat-item.active > [data-lucide] { color: var(--accent); }
        .chat-title { font-size: 13px; font-weight: 550; margin-bottom: 4px; }
        .chat-date { color: var(--muted); opacity: 1; font-size: 11px; }
        .chat-actions { gap: 2px; }
        .chat-item:focus-within .chat-actions, .chat-item.active .chat-actions { opacity: 1; }
        .chat-action-btn { width: 30px; height: 34px; padding: 0; border: 1px solid transparent; background: transparent; border-radius: 7px; --icon-size: 15px; }
        .chat-item.active .chat-action-btn { background: transparent; }
        .chat-action-btn:hover, .chat-item.active .chat-action-btn:hover { background: #283a32; border-color: var(--line-hover); transform: none; }
        .sidebar-footer { padding: 16px 18px; color: var(--muted); font-size: 11px; display: flex; align-items: center; gap: 8px; border-top: 1px solid var(--line); --icon-size: 15px; }
        .main-content { min-width: 0; overflow: hidden; }
        .chat-header { min-height: 80px; padding: 16px 28px; background: var(--panel); border-color: var(--line); }
        .chat-header-left { gap: 12px; }
        .header-mark { width: 40px; height: 40px; border-radius: 11px; background: var(--panel-soft); border-color: var(--line); --icon-size: 20px; flex-shrink: 0; }
        .header-titles { min-width: 0; }
        .header-eyebrow { margin-bottom: 4px; font-size: 9px; }
        .chat-header h1 { font-size: 17px; font-weight: 600; letter-spacing: -.3px; }
        .rename-btn { width: 40px; height: 40px; margin-left: auto; flex-shrink: 0; padding: 8px; color: var(--muted); background: transparent; border: 1px solid var(--line); border-radius: 10px; }
        .rename-btn:hover { background: var(--panel-soft); border-color: var(--line-hover); color: var(--ink); transform: none; }
        .messages-container { padding: 36px clamp(20px, 5vw, 72px); min-height: 0; background: radial-gradient(#26362f 0.7px, transparent 0.7px); background-size: 24px 24px; }
        .message { max-width: 820px; margin: 0 auto 24px; }
        .message-label { color: var(--muted); opacity: 1; letter-spacing: 1px; margin-bottom: 9px; display: flex; align-items: center; gap: 7px; --icon-size: 14px; }
        .message-content { padding: 18px 22px; font-size: 14px; line-height: 1.75; border-radius: var(--radius); border: 1px solid var(--line); overflow-wrap: anywhere; }
        .message:hover .message-content { transform: none; }
        .question { background: #18221e; box-shadow: none; }
        .answer { background: #121b17; color: var(--ink); border-color: #365044; box-shadow: inset 3px 0 0 #547d68; }
        .sources-section { border-top: 1px solid var(--line); padding-top: 18px; margin-top: 20px; }
        .sources-title { opacity: 1; color: var(--muted); display: flex; align-items: center; gap: 8px; --icon-size: 15px; }
        .source-item { max-width: 100%; text-align: left; color: var(--ink); background: var(--panel-soft); border: 1px solid var(--line); border-radius: 10px; padding: 12px; font-size: 12px; }
        .source-item:hover { background: #24372d; border-color: var(--line-hover); box-shadow: none; transform: none; }
        .source-item:hover .source-icon { transform: none; }
        .source-icon { color: var(--accent); opacity: 1; }
        .source-excerpt { color: var(--muted); font-size: 12px; }
        .input-area { background: var(--panel); border-top: 1px solid var(--line); padding: 18px clamp(20px, 5vw, 72px) 14px; }
        .file-upload-area, .input-wrapper, .composer-hint { max-width: 820px; margin-left: auto; margin-right: auto; }
        .input-wrapper { gap: 10px; align-items: stretch; }
        .file-input-label { display: inline-flex; align-items: center; gap: 9px; min-height: 42px; padding: 9px 13px; font-size: 12px; color: var(--ink); background: var(--panel-soft); border: 1px dashed #587365; border-radius: 10px; }
        .file-input-label:hover { transform: none; background: #24372d; border-color: var(--accent); box-shadow: none; }
        .file-input { display: block; position: absolute; width: 1px; height: 1px; opacity: 0; overflow: hidden; }
        .file-input-wrapper:focus-within .file-input-label { outline: 2px solid var(--accent); outline-offset: 3px; }
        .file-tag { min-width: 0; max-width: 100%; padding: 6px 8px 6px 10px; gap: 7px; border-radius: 8px; border-color: var(--line); background: var(--panel-soft); color: var(--muted); --icon-size: 15px; }
        .file-tag-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .file-tag:hover { transform: none; border-color: var(--line-hover); }
        .file-tag-remove { display: inline-flex; justify-content: center; align-items: center; min-width: 28px; height: 28px; padding: 4px; background: transparent; color: var(--muted); border-radius: 6px; }
        .file-tag-remove:hover { color: #ffb4b4; background: #422424; transform: none; }
        .question-input { min-width: 0; min-height: 80px; padding: 15px 17px; font-size: 14px; background: #0d1411; border: 1px solid #3a5045; border-radius: var(--radius); }
        .question-input::placeholder { color: #96a99e; }
        .question-input:focus, .modal-input:focus, .edit-input:focus { background: #101c16; border-color: var(--accent); box-shadow: 0 0 0 3px #a6edd515; }
        .send-btn { align-self: flex-end; display: inline-flex; align-items: center; justify-content: center; width: 46px; height: 46px; padding: 0; margin-bottom: 1px; flex-shrink: 0; --icon-size: 22px; }
        .composer-hint { margin-top: 10px; color: var(--muted); font-size: 10px; }
        .empty-state { max-width: 620px; margin: clamp(12px, 6vh, 64px) auto; padding: 28px 12px; opacity: 1; animation: fadeIn .3s ease-out; }
        .empty-state-mark { width: 68px; height: 68px; border-radius: 20px; margin-bottom: 26px; --icon-size: 30px; box-shadow: 0 0 0 7px #a6edd507; }
        .empty-state h2 { font-size: clamp(28px, 3.5vw, 42px); font-weight: 550; line-height: 1.15; letter-spacing: -1.7px; margin-bottom: 16px; }
        .empty-state h2 span { color: var(--accent); }
        .empty-state p { color: var(--muted); font-size: 14px; line-height: 1.7; font-weight: 400; opacity: 1; max-width: 390px; margin: auto; }
        .welcome-steps { display: flex; justify-content: center; gap: 8px; margin-top: 28px; flex-wrap: wrap; }
        .welcome-step { display: inline-flex; align-items: center; gap: 8px; padding: 10px 13px; background: var(--panel); border: 1px solid var(--line); border-radius: 9px; color: var(--muted); font-size: 11px; --icon-size: 16px; }
        .welcome-step [data-lucide] { color: var(--accent); }
        .list-empty { padding: 22px 10px; color: var(--muted); text-align: center; font-size: 12px; line-height: 1.7; }
        .modal-content, .file-viewer-content { background: var(--panel); border: 1px solid #45604f; border-radius: 16px; color: var(--ink); }
        .modal-content { padding: 26px; }
        .modal-header { font-size: 20px; }
        .modal-input, .edit-input { background: #0d1411; border-color: var(--line); border-radius: 10px; font-size: 14px; }
        .modal-btn, .edit-btn { min-height: 42px; padding: 10px 18px; font-size: 13px; letter-spacing: 0; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .modal-btn-cancel, .edit-btn-cancel { border-color: var(--line); background: var(--panel-soft); }
        .modal-btn-cancel:hover, .edit-btn-cancel:hover { transform: none; }
        .message-actions { opacity: 1; }
        .message-action-btn { color: var(--muted); display: inline-flex; align-items: center; gap: 6px; min-height: 32px; padding: 5px 9px; --icon-size: 14px; }
        .message-action-btn:hover { transform: none; }
        .file-viewer-header { background: var(--panel-soft); border-bottom: 1px solid var(--line); padding: 18px 24px; }
        .file-viewer-title { font-size: 16px; min-width: 0; }
        #file-viewer-filename { overflow-wrap: anywhere; }
        .file-viewer-close { color: var(--muted); background: transparent; border-color: var(--line); flex-shrink: 0; }
        .file-viewer-close:hover { color: var(--ink); background: #283a32; transform: none; }
        .file-content { font-size: 13px; line-height: 1.8; }
        .search-info { background: var(--accent-soft); color: var(--ink); border-left: 3px solid var(--accent); font-size: 12px; }
        .highlight { background: #a6edd5; color: #10271e; box-shadow: none; padding: 2px 3px; }
        ::-webkit-scrollbar { width: 7px; height: 7px; }
        ::-webkit-scrollbar-thumb { background: #354c40; border: 0; }
        @media (max-width: 1024px) {
            .sidebar { width: 240px; }
            .chat-actions { opacity: 1; }
            .chat-item { gap: 6px; padding: 9px 8px; }
        }
        @media (max-width: 768px) {
            body { overflow: hidden; }
            .container { padding: 8px; gap: 8px; height: 100dvh; min-height: 0; }
            .sidebar { width: 100%; max-height: 192px; border: 1px solid var(--line); border-radius: 12px; }
            .sidebar-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px; border-bottom: 0; }
            .workspace-brand { margin: 0; gap: 8px; }
            .brand-caption, .sidebar-section-label, .sidebar-footer { display: none; }
            .brand-name { font-size: 15px; white-space: nowrap; }
            .brand-mark { width: 34px; height: 34px; --icon-size: 19px; }
            .new-chat-btn { width: auto; min-height: 44px; padding: 10px 13px; font-size: 12px; gap: 6px; }
            .chats-list { flex: 0 1 auto; padding: 0 10px 10px; }
            .chat-item { min-width: 230px; max-width: 260px; margin: 0; }
            .chat-action-btn { width: 34px; height: 44px; }
            .list-empty { padding: 2px 2px 0; text-align: left; font-size: 11px; }
            .main-content { min-height: 0; border-radius: 12px; }
            .chat-header { min-height: 68px; padding: 12px 14px; }
            .chat-header h1 { font-size: 15px; }
            .header-mark { display: none; }
            .rename-btn, .file-tag-remove, .file-viewer-close { min-width: 44px; height: 44px; }
            .messages-container { padding: 20px 14px; }
            .empty-state { margin: 12px auto; padding: 12px 0; }
            .empty-state h2 { font-size: 31px; letter-spacing: -1px; }
            .empty-state-mark { width: 56px; height: 56px; margin-bottom: 20px; --icon-size: 26px; }
            .empty-state p { font-size: 13px; }
            .welcome-steps { gap: 6px; margin-top: 20px; }
            .welcome-step { padding: 8px 10px; }
            .input-area { padding: 12px; }
            .file-input-label { min-height: 44px; }
            .question-input { font-size: 16px; min-height: 72px; padding: 12px; }
            .composer-hint { font-size: 10px; }
            .message-content { padding: 14px; font-size: 14px; }
            .source-excerpt { max-width: 100%; }
            .file-viewer-body { padding: 18px; }
            .edit-mode { flex-direction: column; }
            .edit-actions { flex-direction: row; }
        }
        /* Workflow feedback stays in the workspace, without blocking browser alerts. */
        [hidden] { display: none !important; }
        .skip-link { position: fixed; z-index: 3000; top: 8px; left: 12px; transform: translateY(-160%); background: var(--accent); color: #10271e; padding: 12px 18px; border-radius: 8px; }
        .skip-link:focus { transform: none; }
        button:disabled { opacity: .45; cursor: not-allowed; transform: none; }
        .main-content { position: relative; }
        .workspace-notice { display: flex; align-items: center; gap: 12px; padding: 12px 20px; background: #1c3027; border-bottom: 1px solid #3c6350; flex-shrink: 0; font-size: 13px; line-height: 1.5; }
        .workspace-notice[data-kind="error"] { background: #32221e; border-color: #785140; }
        #notice-message { flex: 1; min-width: 0; overflow-wrap: anywhere; }
        .notice-action { background: transparent; border: 1px solid var(--line-hover); border-radius: 8px; color: var(--ink); padding: 7px 10px; min-height: 36px; cursor: pointer; white-space: nowrap; }
        .notice-dismiss { width: 36px; height: 36px; padding: 8px; flex-shrink: 0; color: var(--muted); border: 0; background: transparent; cursor: pointer; }
        .chat-search { margin: 14px 14px 0; display: flex; align-items: center; gap: 8px; border: 1px solid var(--line); border-radius: 9px; padding: 0 10px; color: var(--muted); }
        .chat-search:focus-within { border-color: var(--accent); }
        .chat-search input { min-width: 0; width: 100%; padding: 10px 0; min-height: 40px; background: transparent; border: 0; color: var(--ink); font-size: 12px; outline: none; }
        .sidebar-section-label { display: flex; justify-content: space-between; padding-top: 16px; }
        .chat-item { gap: 2px; padding: 0 6px 0 0; }
        .chat-select { display: flex; flex: 1; align-items: center; gap: 9px; text-align: left; min-width: 0; min-height: 62px; padding: 10px; border: 0; background: transparent; color: var(--ink); cursor: pointer; border-radius: 9px; }
        .chat-select > [data-lucide] { color: var(--muted); }
        .chat-select .chat-title, .chat-select .chat-date { display: block; }
        .chat-actions { opacity: 1; }
        .chat-select[aria-pressed="true"] > [data-lucide] { color: var(--accent); }
        .welcome-cta { display: inline-flex; width: auto; margin-top: 26px; padding: 12px 22px; }
        .prompt-suggestions { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin-top: 24px; }
        .prompt-chip { padding: 11px 14px; min-height: 44px; border: 1px solid #426554; background: var(--panel-soft); color: var(--ink); border-radius: 10px; cursor: pointer; font-size: 12px; text-align: left; }
        .prompt-chip:hover { background: var(--accent-soft); border-color: var(--accent); }
        .upload-guidance { color: var(--muted); font-size: 11px; line-height: 1.6; margin-top: 8px; }
        .upload-status { color: var(--accent); font-size: 12px; line-height: 1.5; overflow-wrap: anywhere; }
        .upload-status:not(:empty) { margin-top: 8px; }
        .input-area:has(.file-input:disabled) .file-input-label { opacity: .5; cursor: not-allowed; }
        .answer-text, .question-text { white-space: pre-wrap; overflow-wrap: anywhere; }
        .answer-text + .message-action-btn { margin-top: 14px; }
        .answer-status { display: flex; gap: 12px; align-items: flex-start; }
        .answer-status p { color: var(--muted); font-size: 12px; line-height: 1.65; margin-top: 5px; }
        .answer-status > [data-lucide] { margin-top: 4px; color: var(--accent); }
        .answer-status [data-lucide="loader-circle"] { animation: loading-spin 1s linear infinite; }
        @keyframes loading-spin { to { transform: rotate(360deg); } }
        .message, .sources-section, .file-tag { animation: none; }
        dialog.modal, dialog.file-viewer-modal { inset: 0; margin: 0; max-width: none; max-height: none; border: 0; padding: 16px; color: var(--ink); }
        dialog[open].modal, dialog[open].file-viewer-modal { display: flex; }
        dialog::backdrop { background: #0008; }
        .dialog-description { font-size: 14px; line-height: 1.7; color: var(--muted); margin: -8px 0 24px; overflow-wrap: anywhere; }
        .dialog-error { color: #ffc0a9; font-size: 13px; margin-bottom: 16px; }
        .danger-btn { background: #603b32; color: #ffe0d5; border: 1px solid #a76b57; }
        .download-link { color: var(--accent); text-decoration: underline; }
        .drop-overlay { display: none; position: absolute; inset: 12px; z-index: 10; pointer-events: none; align-items: center; justify-content: center; border: 2px dashed var(--accent); background: #10271ef2; border-radius: 12px; color: var(--accent); font-size: 20px; }
        .is-dragging .drop-overlay { display: flex; }
        @media (max-width: 768px) {
            .sidebar { max-height: 238px; }
            .chat-search { margin: 0 12px 8px; }
            .chat-search input { min-height: 34px; padding: 6px 0; }
            .sidebar-section-label { display: none; }
            .chat-select { min-height: 58px; }
            .workspace-notice { padding: 10px 12px; gap: 6px; font-size: 12px; flex-wrap: wrap; }
            .notice-action, .notice-dismiss { min-height: 44px; }
            .upload-guidance { font-size: 10px; }
            .welcome-cta { font-size: 13px; }
            .modal-actions { gap: 8px; flex-wrap: wrap; }
            .modal-btn { min-height: 44px; }
            .empty-state h2 { font-size: 26px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; scroll-behavior: auto !important; }
        }
    </style>
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to conversation</a>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="workspace-brand">
                    <span class="brand-mark"><i data-lucide="notebook-tabs"></i></span>
                    <div><div class="brand-name">Doc Q&A</div><div class="brand-caption">Make room for clarity.</div></div>
                </div>
                <button class="new-chat-btn" onclick="createNewChat()"><i data-lucide="plus"></i> New Chat</button>
            </div>
            <label class="chat-search"><i data-lucide="search"></i><input id="chat-search" type="search" placeholder="Find a conversation…" aria-label="Search conversations" autocomplete="off"></label>
            <div class="sidebar-section-label">Your conversations <span id="chat-count">0</span></div>
            <div class="chats-list" id="chats-list"></div>
            <div class="sidebar-footer"><i data-lucide="book-open"></i> A workspace for your documents</div>
        </div>

        <main class="main-content" id="main-content" tabindex="-1">
            <div class="drop-overlay" aria-hidden="true">Drop documents to add them</div>
            <div class="chat-header">
                <div class="chat-header-left">
                    <span class="header-mark"><i data-lucide="messages-square"></i></span>
                    <div class="header-titles"><div class="header-eyebrow">Document workspace</div><h1 id="chat-title">Your next discovery starts here</h1></div>
                    <button class="rename-btn" id="rename-btn" onclick="openRenameModal()" style="display: none;" title="Rename chat" aria-label="Rename chat"><i data-lucide="square-pen"></i></button>
                </div>
            </div>

            <div class="workspace-notice" id="workspace-notice" role="status" aria-live="polite" hidden>
                <span id="notice-message"></span><button class="notice-action" id="notice-action" hidden>Try again</button>
                <button class="notice-dismiss" id="notice-dismiss" aria-label="Dismiss notification"><i data-lucide="x"></i></button>
            </div>
            <div class="messages-container" id="messages-container">
                <div class="empty-state">
                    <span class="empty-state-mark"><i data-lucide="scan-text"></i></span>
                    <h2>Your documents.<br><span>Clear answers.</span></h2>
                    <p>Start a conversation with your files. Find the details, connect the dots, and follow the sources.</p>
                    <button class="new-chat-btn welcome-cta" data-new-chat onclick="createNewChat()"><i data-lucide="plus"></i> Start a conversation</button>
                    <div class="welcome-steps">
                        <span class="welcome-step"><i data-lucide="file-up"></i> Add documents</span>
                        <span class="welcome-step"><i data-lucide="messages-square"></i> Ask a question</span>
                        <span class="welcome-step"><i data-lucide="book-open"></i> Explore sources</span>
                    </div>
                </div>
            </div>

            <div class="input-area" id="input-area" style="display: none;">
                <div class="file-upload-area">
                    <div class="file-input-wrapper">
                        <label class="file-input-label" for="file-input"><i data-lucide="file-up"></i> Add document</label>
                        <input type="file" id="file-input" class="file-input" accept=".pdf,.docx,.txt,.csv,.xlsx,.png,.jpg,.jpeg,.tiff,.bmp" multiple aria-describedby="upload-guidance" onchange="uploadFile()">
                    </div>
                    <p class="upload-guidance" id="upload-guidance">PDF, Word, text, spreadsheets, or images · Up to 20 MB each · Drag & drop supported</p>
                    <div class="upload-status" id="upload-status" role="status" aria-live="polite"></div>
                    <div class="uploaded-files" id="uploaded-files"></div>
                </div>

                <div class="input-wrapper">
                    <textarea id="question-input" class="question-input" rows="2" aria-label="Question about your documents" aria-describedby="composer-help" placeholder="What would you like to know?" onkeydown="handleKeyPress(event)"></textarea>
                    <button class="send-btn" id="send-question" onclick="askQuestion()" title="Send question" aria-label="Send question" disabled><i data-lucide="arrow-up"></i></button>
                </div>
                <div class="composer-hint" id="composer-help">Add a document to get started.</div>
            </div>
        </main>
    </div>

    <dialog class="modal" id="rename-modal" aria-labelledby="rename-title" onclick="if(event.target === this) closeRenameModal()">
        <div class="modal-content">
            <div class="modal-header" id="rename-title">Rename Chat</div>
            <input type="text" id="rename-input" class="modal-input" aria-label="Chat name" placeholder="Enter chat name..." maxlength="100">
            <p class="dialog-error" id="rename-error" role="alert"></p>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-cancel" onclick="closeRenameModal()">Cancel</button>
                <button class="modal-btn modal-btn-save" id="rename-save" onclick="saveChatTitle()"><i data-lucide="check"></i> Save</button>
            </div>
        </div>
    </dialog>

    <dialog class="modal" id="confirm-dialog" aria-labelledby="confirm-title" aria-describedby="confirm-description">
        <div class="modal-content">
            <div class="modal-header" id="confirm-title">Confirm action</div>
            <p class="dialog-description" id="confirm-description"></p>
            <div class="modal-actions"><button class="modal-btn modal-btn-cancel" id="confirm-cancel">Keep it</button><button class="modal-btn danger-btn" id="confirm-accept">Delete</button></div>
        </div>
    </dialog>

    <dialog class="file-viewer-modal" id="file-viewer-modal" aria-labelledby="file-viewer-filename" onclick="if(event.target === this) closeFileViewer()">
        <div class="file-viewer-content">
            <div class="file-viewer-header">
                <div class="file-viewer-title">
                    <i data-lucide="file-text"></i>
                    <span id="file-viewer-filename">Document</span>
                </div>
                <button class="file-viewer-close" onclick="closeFileViewer()" aria-label="Close document"><i data-lucide="x"></i></button>
            </div>
            <div class="file-viewer-body">
                <div class="search-info" id="search-info"></div>
                <div class="file-content" id="file-content"></div>
            </div>
        </div>
    </dialog>

</body>
</html>
