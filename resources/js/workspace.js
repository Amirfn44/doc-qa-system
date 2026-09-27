const byId = (id) => document.getElementById(id);
const escapeHtml = (value = '') => String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
}[char]));
const icon = (name) => `<i data-lucide="${name}" aria-hidden="true"></i>`;
function readSession(key, fallback) {
    try { return JSON.parse(sessionStorage.getItem(`docqa:${key}`)) ?? fallback; } catch { return fallback; }
}
function writeSession(key, value) {
    try { sessionStorage.setItem(`docqa:${key}`, JSON.stringify(value)); } catch { /* Storage is optional. */ }
}

const state = {
    chatId: null, chat: null, chats: [], loading: false, selection: 0, creating: false,
    uploading: null, submitting: new Set(), drafts: readSession('drafts', {}),
    pending: readSession('pending', {}), timers: new Map(), renameId: null,
};
const welcomeMarkup = byId('messages-container').innerHTML;
let noticeAction = null;
let noticeTimer = null;
let confirmResolve = null;
let viewerRequest = 0;

async function request(url, options = {}) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 30000);
    try {
        const headers = { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content };
        if (options.body && !(options.body instanceof FormData)) headers['Content-Type'] = 'application/json';
        const response = await fetch(url, { ...options, headers, signal: controller.signal });
        const data = await response.json().catch(() => null);
        if (!response.ok) {
            const validation = data?.errors && Object.values(data.errors).flat()[0];
            const message = response.status === 419 ? 'Your session expired. Refresh the page and try again.'
                : response.status >= 500 ? 'The server could not complete that request. Please try again.'
                    : validation || data?.error || data?.message || 'The request could not be completed.';
            throw new Error(message);
        }
        if (data === null) throw new Error('The server returned an unreadable response. Refresh the page and try again.');
        return data;
    } catch (error) {
        if (error.name === 'AbortError') throw new Error('The connection timed out. Your draft is safe; check your connection and try again.');
        if (error instanceof TypeError) throw new Error('Cannot reach the server. Check your connection and try again.');
        throw error;
    } finally { clearTimeout(timer); }
}

function notify(message, kind = 'info', action = null, label = 'Try again') {
    clearTimeout(noticeTimer);
    byId('workspace-notice').hidden = false;
    byId('workspace-notice').dataset.kind = kind;
    byId('notice-message').textContent = message;
    noticeAction = action;
    byId('notice-action').hidden = !action;
    byId('notice-action').textContent = label;
    if (kind === 'success' && !action) noticeTimer = setTimeout(() => { byId('workspace-notice').hidden = true; }, 6000);
}

function saveDraft() {
    if (state.chatId !== null) {
        state.drafts[state.chatId] = byId('question-input').value;
        writeSession('drafts', state.drafts);
    }
}

function updateComposer() {
    const input = byId('question-input');
    const job = state.pending[state.chatId];
    const working = job && ['waiting', 'paused'].includes(job.status);
    const uploading = state.uploading === state.chatId && state.chatId !== null;
    const hasFiles = Boolean(state.chat?.files?.length);
    input.disabled = state.loading || !state.chat || state.submitting.has(state.chatId);
    byId('send-question').disabled = !hasFiles || !input.value.trim() || working || uploading || state.submitting.has(state.chatId) || state.loading;
    byId('file-input').disabled = uploading || working || state.loading;
    byId('input-area').setAttribute('aria-busy', String(uploading || state.submitting.has(state.chatId)));
    byId('composer-help').textContent = state.loading ? 'Opening conversation…'
        : uploading ? 'Uploading documents. You can keep writing your question.'
            : working ? 'Your answer is on its way. You can draft your next question or visit another chat.'
                : !hasFiles ? 'Add a document before sending. You can write your question now.'
                    : 'Enter to send · Shift + Enter for a new line · Draft saved in this tab';
    input.style.height = 'auto';
    input.style.height = `${Math.min(Math.max(input.scrollHeight, 72), 160)}px`;
}

function formatDate(value) {
    const minutes = Math.max(0, Math.floor((Date.now() - new Date(value)) / 60000));
    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (minutes < 1440) return `${Math.floor(minutes / 60)}h ago`;
    return new Date(value).toLocaleDateString();
}

function renderChats({ revealActive = false } = {}) {
    const search = byId('chat-search').value.trim().toLowerCase();
    const chats = state.chats.filter((chat) => (chat.title || 'New Chat').toLowerCase().includes(search));
    byId('chat-count').textContent = String(state.chats.length);
    byId('chats-list').innerHTML = chats.length ? chats.map((chat) => `
        <div class="chat-item ${Number(chat.id) === state.chatId ? 'active' : ''}">
            <button class="chat-select" data-action="select" data-id="${chat.id}" aria-pressed="${Number(chat.id) === state.chatId}">
                ${icon('messages-square')}<span class="chat-info"><span class="chat-title">${escapeHtml(chat.title || 'New Chat')}</span>
                <span class="chat-date">${state.pending[chat.id]?.status === 'waiting' ? 'Preparing answer…' : escapeHtml(formatDate(chat.updated_at))}</span></span>
            </button>
            <div class="chat-actions">
                <button class="chat-action-btn" data-action="rename" data-id="${chat.id}" title="Rename" aria-label="Rename conversation">${icon('square-pen')}</button>
                <button class="chat-action-btn" data-action="delete-chat" data-id="${chat.id}" title="Delete" aria-label="Delete conversation">${icon('trash-2')}</button>
            </div>
        </div>`).join('') : `<div class="list-empty">${search ? 'No conversations match your search.' : 'A fresh page.<br>Create your first conversation above.'}</div>`;
    if (revealActive) byId('chats-list').querySelector('.chat-item.active')?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
}

async function loadChats() {
    try {
        state.chats = await request('/api/chats');
        renderChats();
        return true;
    } catch (error) {
        byId('chats-list').innerHTML = '<div class="list-empty">Conversations could not be loaded.</div>';
        notify(error.message, 'error', loadChats);
        return false;
    }
}

async function createNewChat() {
    if (state.creating) return;
    state.creating = true;
    document.querySelectorAll('[data-new-chat], .new-chat-btn').forEach((button) => { button.disabled = true; });
    try {
        const chat = await request('/api/chats', { method: 'POST', body: JSON.stringify({ title: 'New Chat' }) });
        byId('chat-search').value = '';
        await loadChats();
        await selectChat(chat.chat_id);
        byId('file-input').focus();
    } catch (error) { notify(error.message, 'error', createNewChat); }
    finally {
        state.creating = false;
        document.querySelectorAll('[data-new-chat], .new-chat-btn').forEach((button) => { button.disabled = false; });
    }
}

async function selectChat(id, { refresh = false } = {}) {
    saveDraft();
    const changed = state.chatId !== Number(id);
    const selection = ++state.selection;
    state.chatId = Number(id);
    state.loading = true;
    if (changed) state.chat = null;
    byId('input-area').style.display = 'block';
    byId('question-input').value = state.drafts[id] || '';
    byId('messages-container').setAttribute('aria-busy', 'true');
    if (changed) byId('messages-container').innerHTML = '<div class="list-empty">Opening conversation…</div>';
    renderChats({ revealActive: changed });
    updateComposer();
    try {
        const chat = await request(`/api/chats/${id}`);
        if (selection !== state.selection) return;
        state.chat = chat;
        const pending = state.pending[id];
        if (pending && chat.messages.some((message) => Number(message.id) === Number(pending.messageId) && message.answer)) {
            // The cached status may have expired, but the saved answer is authoritative.
            delete state.pending[id];
            clearTimeout(state.timers.get(Number(id)));
            persistPending();
        }
        writeSession('active-chat', Number(id));
        byId('chat-title').textContent = chat.title || 'New Chat';
        byId('rename-btn').style.display = 'inline-flex';
        renderMessages({ scroll: changed || !refresh });
        renderUploadedFiles();
    } catch (error) {
        if (selection !== state.selection) return;
        notify(error.message, 'error', () => selectChat(id));
    } finally {
        if (selection === state.selection) {
            state.loading = false;
            byId('messages-container').setAttribute('aria-busy', 'false');
            updateComposer();
        }
    }
}

function renderMessages({ scroll = false } = {}) {
    if (!state.chat) return;
    const container = byId('messages-container');
    const previousTop = container.scrollTop;
    const nearBottom = container.scrollHeight - previousTop - container.clientHeight < 100;
    const messages = state.chat.messages || [];
    if (!messages.length) {
        const ready = state.chat.files.length > 0;
        container.innerHTML = `<div class="empty-state"><span class="empty-state-mark">${icon(ready ? 'messages-square' : 'file-up')}</span>
            <h2>${ready ? 'Your documents are ready.<br><span>What’s your first question?</span>' : 'Bring your documents.<br><span>We’ll find the details.</span>'}</h2>
            <p>${ready ? 'Choose a starting point, or write your own question below.' : 'Add a document below, or drag it into this workspace.'}</p>
            ${ready ? `<div class="prompt-suggestions">${['Summarize the key points.', 'What are the main conclusions?', 'Which details should I pay attention to?'].map((prompt) => `<button class="prompt-chip" data-action="prompt" data-prompt="${escapeHtml(prompt)}">${escapeHtml(prompt)}</button>`).join('')}</div>` : ''}</div>`;
        return;
    }
    container.innerHTML = messages.map((message) => {
        const pending = state.pending[state.chatId];
        const job = Number(pending?.messageId) === Number(message.id) ? pending : null;
        const waiting = job?.status === 'waiting';
        const paused = job?.status === 'paused';
        const body = message.answer
            ? `<div class="answer-text">${escapeHtml(message.answer)}</div>
                <button class="message-action-btn" data-action="copy" data-id="${message.id}">${icon('copy')} Copy answer</button>
                ${message.citations?.length ? `<div class="sources-section"><div class="sources-title">${icon('book-open')} Sources</div>${message.citations.map((citation) => {
                    const filename = typeof citation === 'string' ? citation : citation.filename;
                    return `<button class="source-item" data-action="source" data-filename="${escapeHtml(filename)}" data-id="${message.id}" title="Open source document">${icon('file-text')}<span><strong>${escapeHtml(filename)}</strong>${citation.excerpt ? `<small class="source-excerpt">${escapeHtml(citation.excerpt)}</small>` : ''}</span></button>`;
                }).join('')}</div>` : ''}`
            : `<div class="answer-status" role="status">${icon(waiting ? 'loader-circle' : 'circle-help')}<div><strong>${waiting ? 'Preparing your answer' : paused ? 'Still waiting for a response' : 'No answer available'}</strong><p>${escapeHtml(job?.error || (waiting ? 'Larger documents and the first question can take a few minutes. You can browse other chats while you wait.' : 'Try this question again when your documents are ready.'))}</p></div></div>
                ${waiting ? '' : `<button class="message-action-btn" data-action="${paused ? 'check-again' : 'retry'}" data-id="${message.id}">${icon('rotate-cw')} ${paused ? 'Check again' : 'Retry question'}</button>`}`;
        return `<div class="message" id="message-${message.id}"><div class="message-label">You</div><div class="message-content question">
            <div class="question-text" id="question-text-${message.id}">${escapeHtml(message.question)}</div><div class="message-actions"><button class="message-action-btn" data-action="edit" data-id="${message.id}">${icon('square-pen')} Edit</button></div></div></div>
            <div class="message"><div class="message-label">${icon('scan-text')} Assistant</div><div class="message-content answer">${body}</div></div>`;
    }).join('');
    container.scrollTop = scroll || nearBottom ? container.scrollHeight : previousTop;
}

function renderUploadedFiles() {
    byId('uploaded-files').innerHTML = (state.chat?.files || []).map((file) => `<div class="file-tag">${icon('file-text')}
        <span class="file-tag-name" title="${escapeHtml(file.original_name)}">${escapeHtml(file.original_name)}</span>
        <button class="file-tag-remove" data-action="delete-file" data-id="${file.id}" aria-label="Remove ${escapeHtml(file.original_name)}">${icon('x')}</button></div>`).join('');
}

async function uploadFile(files = byId('file-input').files) {
    if (!state.chat || state.uploading !== null) return;
    const chatId = state.chatId;
    if (['waiting', 'paused'].includes(state.pending[chatId]?.status)) return notify('Wait for the current answer before changing its documents.');
    const selected = Array.from(files || []);
    if (!selected.length) return;
    const invalid = selected.find((file) => !/\.(pdf|docx|txt|csv|xlsx|png|jpg|jpeg|tiff|bmp)$/i.test(file.name) || file.size > 20 * 1024 * 1024);
    byId('file-input').value = '';
    if (invalid) return notify(`${invalid.name}: choose a supported document or image under 20 MB.`, 'error');
    state.uploading = chatId;
    updateComposer();
    let uploaded = 0;
    try {
        for (const file of selected) {
            byId('upload-status').textContent = `Uploading ${uploaded + 1} of ${selected.length}: ${file.name}`;
            const body = new FormData();
            body.append('file', file);
            await request(`/api/chats/${chatId}/upload`, { method: 'POST', body });
            uploaded++;
        }
        notify(`${uploaded} document${uploaded === 1 ? '' : 's'} added. You’re ready to ask a question.`, 'success');
    } catch (error) { notify(`${uploaded ? `${uploaded} document(s) added. ` : ''}${error.message}`, 'error'); }
    finally {
        state.uploading = null;
        byId('upload-status').textContent = '';
        if (state.chatId === chatId) {
            await selectChat(chatId, { refresh: true });
            if (uploaded) byId('question-input').focus();
        }
        await loadChats();
        updateComposer();
    }
}

function persistPending() { writeSession('pending', state.pending); }
function beginPolling(chatId, data, question) {
    state.pending[chatId] = { queryId: data.query_id, messageId: data.message_id, question, startedAt: Date.now(), status: 'waiting', failures: 0 };
    persistPending();
    pollForAnswer(chatId);
    updateComposer();
    renderChats();
}

async function askQuestion() {
    const chatId = state.chatId;
    const question = byId('question-input').value.trim();
    if (!state.chat || !question || byId('send-question').disabled) return;
    await submitQuestion(chatId, question);
}

async function submitQuestion(chatId, question, messageId = null) {
    if (state.submitting.has(chatId) || state.uploading === chatId || state.loading || ['waiting', 'paused'].includes(state.pending[chatId]?.status)) return;
    if (!state.chat?.files.length) return notify('Add a document before asking a question.', 'info');
    state.submitting.add(chatId);
    updateComposer();
    try {
        const data = await request(messageId ? `/api/chats/${chatId}/messages/${messageId}` : `/api/chats/${chatId}/ask`, {
            method: messageId ? 'PATCH' : 'POST', body: JSON.stringify({ question }),
        });
        if (!data.query_id) throw new Error(data.error || 'The question could not be submitted. Your draft is safe.');
        if (!messageId) {
            state.drafts[chatId] = '';
            writeSession('drafts', state.drafts);
            if (state.chatId === chatId) byId('question-input').value = '';
        }
        beginPolling(chatId, { ...data, message_id: data.message_id || messageId }, question);
        if (state.chatId === chatId) await selectChat(chatId, { refresh: true });
        await loadChats();
    } catch (error) { notify(error.message, 'error'); }
    finally { state.submitting.delete(chatId); updateComposer(); }
}

async function pollForAnswer(chatId) {
    clearTimeout(state.timers.get(Number(chatId)));
    const job = state.pending[chatId];
    if (!job || job.status !== 'waiting') return;
    try {
        const result = await request(`/api/check-status?query_id=${encodeURIComponent(job.queryId)}`);
        if (state.pending[chatId] !== job) return;
        job.failures = 0;
        if (result.status === 'completed') {
            delete state.pending[chatId];
            persistPending();
            if (state.chatId === Number(chatId)) await selectChat(chatId, { refresh: true });
            else notify('An answer is ready in another conversation.', 'success', () => selectChat(chatId), 'View answer');
            await loadChats();
            updateComposer();
            return;
        }
        if (result.status === 'error') {
            job.status = 'error';
            job.error = result.details || 'The answer could not be generated. Check your documents and try again.';
        } else if (Date.now() - job.startedAt > 6 * 60 * 1000) {
            job.status = 'paused';
            job.error = 'This is taking longer than expected. The request may still be running; check again without resending it.';
        }
    } catch (error) {
        if (state.pending[chatId] !== job) return;
        job.failures = (job.failures || 0) + 1;
        if (job.failures >= 3) { job.status = 'paused'; job.error = 'The connection was interrupted. Your question was submitted; check again when connected.'; }
    }
    if (state.pending[chatId] !== job) return;
    persistPending();
    if (job.status === 'waiting') state.timers.set(Number(chatId), setTimeout(() => pollForAnswer(chatId), 3000));
    else {
        if (state.chatId === Number(chatId)) { renderMessages(); updateComposer(); }
        renderChats();
    }
}

function confirmAction(title, description, label) {
    byId('confirm-title').textContent = title;
    byId('confirm-description').textContent = description;
    byId('confirm-accept').textContent = label;
    byId('confirm-dialog').showModal();
    byId('confirm-cancel').focus();
    return new Promise((resolve) => { confirmResolve = resolve; });
}

async function deleteChat(id) {
    if (state.submitting.has(id) || state.uploading === id || ['waiting', 'paused'].includes(state.pending[id]?.status)) return notify('Wait for the current request to finish before deleting this conversation.');
    const chat = state.chats.find((item) => Number(item.id) === id);
    if (!await confirmAction('Delete conversation?', `“${chat?.title || 'New Chat'}” and its uploaded files will be permanently deleted.`, 'Delete conversation')) return;
    try {
        await request(`/api/chats/${id}`, { method: 'DELETE' });
        delete state.drafts[id];
        delete state.pending[id];
        persistPending();
        writeSession('drafts', state.drafts);
        if (state.chatId === id) {
            state.selection++;
            state.chatId = null; state.chat = null; state.loading = false;
            writeSession('active-chat', null);
            byId('chat-title').textContent = 'Your next discovery starts here';
            byId('rename-btn').style.display = 'none';
            byId('input-area').style.display = 'none';
            byId('messages-container').innerHTML = welcomeMarkup;
        }
        await loadChats();
        notify('Conversation deleted.', 'success');
    } catch (error) { notify(error.message, 'error'); }
}

async function deleteFile(id) {
    const chatId = state.chatId;
    if (state.uploading === chatId || state.submitting.has(chatId) || ['waiting', 'paused'].includes(state.pending[chatId]?.status)) return notify('Wait for the current request before removing documents.');
    const file = state.chat?.files.find((item) => Number(item.id) === id);
    if (!file || !await confirmAction('Remove document?', `Remove “${file.original_name}” from this conversation? Existing answers will remain, but this source will no longer be available.`, 'Remove document')) return;
    try {
        await request(`/api/chats/${chatId}/files/${id}`, { method: 'DELETE' });
        if (state.chatId === chatId) await selectChat(chatId, { refresh: true });
        notify('Document removed.', 'success');
    } catch (error) { notify(error.message, 'error'); }
}

function openRenameModal(id = state.chatId) {
    const chat = state.chats.find((item) => Number(item.id) === id) || state.chat;
    if (!chat) return;
    state.renameId = id;
    byId('rename-input').value = chat.title || '';
    byId('rename-error').textContent = '';
    byId('rename-modal').showModal();
    byId('rename-input').focus();
    byId('rename-input').select();
}
function closeRenameModal() { byId('rename-modal').close(); }
async function saveChatTitle() {
    const title = byId('rename-input').value.trim();
    const id = state.renameId;
    if (!title) { byId('rename-error').textContent = 'Enter a name for this conversation.'; return; }
    const button = byId('rename-save');
    if (button.disabled) return;
    button.disabled = true;
    try {
        await request(`/api/chats/${id}/title`, { method: 'PATCH', body: JSON.stringify({ title }) });
        if (state.chatId === id && state.chat) { state.chat.title = title; byId('chat-title').textContent = title; }
        closeRenameModal();
        await loadChats();
        notify('Conversation renamed.', 'success');
    } catch (error) { byId('rename-error').textContent = error.message; }
    finally { button.disabled = false; }
}

function editMessage(id) {
    if (state.submitting.has(state.chatId) || ['waiting', 'paused'].includes(state.pending[state.chatId]?.status)) return notify('Wait for the current answer before editing a question.');
    const message = state.chat.messages.find((item) => Number(item.id) === id);
    if (!message) return;
    byId(`message-${id}`).querySelector('.question').innerHTML = `<div class="edit-mode"><textarea class="edit-input" id="edit-input-${id}" aria-label="Edit question">${escapeHtml(message.question)}</textarea><div class="edit-actions"><button class="edit-btn edit-btn-save" data-action="save-edit" data-id="${id}">Save & ask</button><button class="edit-btn edit-btn-cancel" data-action="cancel-edit">Cancel</button></div></div>`;
    byId(`edit-input-${id}`).focus();
}

async function openFileViewer(filename, messageId) {
    const version = ++viewerRequest;
    const chatId = state.chatId;
    const answer = state.chat?.messages.find((message) => Number(message.id) === messageId)?.answer || '';
    byId('file-viewer-filename').textContent = filename;
    byId('file-content').textContent = 'Opening document…';
    byId('search-info').style.display = 'none';
    byId('file-viewer-modal').showModal();
    try {
        const data = await request(`/api/chats/${chatId}/files/content?filename=${encodeURIComponent(filename)}`);
        if (version !== viewerRequest || !byId('file-viewer-modal').open) return;
        if (data.previewable === false) {
            byId('file-content').textContent = 'This format is available as a download. ';
            const link = document.createElement('a');
            link.href = data.download_url;
            link.textContent = 'Download original document';
            link.className = 'download-link';
            link.setAttribute('download', '');
            byId('file-content').append(link);
            return;
        }
        const words = [...new Set(answer.toLowerCase().match(/\b[a-z]{4,}\b/g) || [])].slice(0, 60);
        const pattern = words.length ? new RegExp(`\\b(${words.join('|')})\\w*\\b`, 'gi') : null;
        let count = 0;
        const content = String(data.content || '');
        let offset = 0;
        byId('file-content').replaceChildren();
        if (pattern) for (const match of content.matchAll(pattern)) {
            byId('file-content').append(document.createTextNode(content.slice(offset, match.index)));
            const mark = document.createElement('mark'); mark.className = 'highlight'; mark.textContent = match[0];
            byId('file-content').append(mark); offset = match.index + match[0].length; count++;
        }
        byId('file-content').append(document.createTextNode(content.slice(offset)));
        byId('search-info').textContent = `${count} terms related to your answer highlighted`;
        byId('search-info').style.display = count ? 'block' : 'none';
    } catch (error) { if (version === viewerRequest) byId('file-content').textContent = error.message; }
}
function closeFileViewer() { viewerRequest++; byId('file-viewer-modal').close(); }

function handleKeyPress(event) {
    if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) { event.preventDefault(); askQuestion(); }
}

Object.assign(window, { createNewChat, uploadFile, askQuestion, handleKeyPress, openRenameModal, closeRenameModal, saveChatTitle, closeFileViewer });
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-action]');
    if (!button || button.disabled) return;
    const id = Number(button.dataset.id);
    const action = button.dataset.action;
    if (action === 'select') selectChat(id);
    if (action === 'rename') openRenameModal(id);
    if (action === 'delete-chat') deleteChat(id);
    if (action === 'delete-file') deleteFile(id);
    if (action === 'prompt') { byId('question-input').value = button.dataset.prompt; saveDraft(); updateComposer(); byId('question-input').focus(); }
    if (action === 'source') openFileViewer(button.dataset.filename, id);
    if (action === 'edit') editMessage(id);
    if (action === 'cancel-edit') renderMessages();
    if (action === 'save-edit') {
        const question = byId(`edit-input-${id}`).value.trim();
        if (question) submitQuestion(state.chatId, question, id);
        else notify('Write a question before saving.', 'error');
    }
    if (action === 'retry') {
        const question = state.chat.messages.find((message) => Number(message.id) === id)?.question;
        if (question) submitQuestion(state.chatId, question, id);
    }
    if (action === 'check-again') {
        const job = state.pending[state.chatId];
        if (job) { job.status = 'waiting'; job.startedAt = Date.now(); job.failures = 0; delete job.error; persistPending(); pollForAnswer(state.chatId); renderMessages(); updateComposer(); }
    }
    if (action === 'copy') {
        try { await navigator.clipboard.writeText(state.chat.messages.find((message) => Number(message.id) === id)?.answer || ''); notify('Answer copied.', 'success'); }
        catch { notify('Clipboard access is unavailable. Select the answer text to copy it.', 'error'); }
    }
});

byId('chat-search').addEventListener('input', renderChats);
byId('question-input').addEventListener('input', () => { saveDraft(); updateComposer(); });
byId('notice-dismiss').addEventListener('click', () => { byId('workspace-notice').hidden = true; });
byId('notice-action').addEventListener('click', () => { const action = noticeAction; byId('workspace-notice').hidden = true; action?.(); });
byId('rename-input').addEventListener('keydown', (event) => { if (event.key === 'Enter' && !event.isComposing) { event.preventDefault(); saveChatTitle(); } });
byId('confirm-accept').addEventListener('click', () => { confirmResolve?.(true); confirmResolve = null; byId('confirm-dialog').close(); });
byId('confirm-cancel').addEventListener('click', () => byId('confirm-dialog').close());
byId('confirm-dialog').addEventListener('close', () => { confirmResolve?.(false); confirmResolve = null; });
window.addEventListener('pagehide', saveDraft);

let dragDepth = 0;
const workspace = byId('main-content');
workspace.addEventListener('dragenter', (event) => {
    if (!Array.from(event.dataTransfer.types).includes('Files')) return;
    event.preventDefault(); dragDepth++; workspace.classList.add('is-dragging');
});
workspace.addEventListener('dragover', (event) => { if (Array.from(event.dataTransfer.types).includes('Files')) event.preventDefault(); });
workspace.addEventListener('dragleave', () => { if (--dragDepth <= 0) { dragDepth = 0; workspace.classList.remove('is-dragging'); } });
workspace.addEventListener('drop', (event) => {
    event.preventDefault(); dragDepth = 0; workspace.classList.remove('is-dragging');
    if (!state.chat) return notify('Create or select a conversation before adding documents.', 'info', createNewChat, 'New conversation');
    uploadFile(event.dataTransfer.files);
});

async function initialize() {
    if (!await loadChats()) return;
    const active = Number(readSession('active-chat', null));
    if (state.chats.some((chat) => Number(chat.id) === active)) await selectChat(active);
    for (const [id, job] of Object.entries(state.pending)) {
        if (!state.chats.some((chat) => Number(chat.id) === Number(id))) { delete state.pending[id]; continue; }
        if (job.status === 'waiting') pollForAnswer(Number(id));
    }
    persistPending();
    updateComposer();
}
initialize();
