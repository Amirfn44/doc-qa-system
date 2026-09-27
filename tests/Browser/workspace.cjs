// Run against the local app: node tests/Browser/workspace.cjs [path-to-playwright]
// All API calls are mocked: this never creates or deletes the user's chats/files.
const assert = require('node:assert/strict');
const { chromium } = require(process.argv[2] || 'playwright');
const url = process.env.APP_TEST_URL || 'http://127.0.0.1:8000';

(async () => {
    const browser = await chromium.launch({ channel: 'chrome', headless: true });
    try {
        const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
        page.setDefaultTimeout(10000);
        const errors = [], dialogs = [], checks = [];
        page.on('pageerror', error => errors.push(error.message));
        page.on('dialog', async dialog => { dialogs.push(dialog.message()); await dialog.dismiss(); });
        const chats = [
            { id: 101, title: 'Research plan', messages: [], files: [], updated_at: new Date().toISOString() },
            { id: 102, title: "Client's notes", messages: [], files: [], updated_at: new Date().toISOString() },
        ];
        let askCount = 0, uploads = 0, deletes = 0, nextMessage = 200;
        let rejectNextQuestion = false, delayChat = null, complete = false, failAnswer = false;
        const pending = new Map();
        const answer = 'First paragraph [keep this detail].\n\nSecond paragraph <img src=x onerror=alert(1)>.';
        await page.route('**/api/**', async route => {
            const req = route.request(), path = new URL(req.url()).pathname, method = req.method();
            const reply = (json, status = 200) => route.fulfill({ status, json });
            if (path === '/api/chats') {
                if (method === 'POST') {
                    const chat = { id: 103, title: 'New Chat', messages: [], files: [], updated_at: new Date().toISOString() };
                    chats.push(chat); return reply({ chat_id: 103 });
                }
                return reply(chats);
            }
            if (path === '/api/check-status') {
                const job = pending.get(new URL(req.url()).searchParams.get('query_id'));
                if (!job) return reply({ status: 'processing' });
                if (failAnswer) return reply({ status: 'error', details: 'The model could not answer. Try again.' });
                if (complete) {
                    job.message.answer = answer;
                    job.message.citations = [{ filename: 'notes.txt', chunk_index: 0, excerpt: 'A source excerpt.' }];
                    return reply({ status: 'completed' });
                }
                return reply({ status: 'processing' });
            }
            const match = path.match(/^\/api\/chats\/(\d+)(.*)$/);
            if (!match) return reply({ error: 'Unexpected endpoint' }, 404);
            const chat = chats.find(item => item.id === Number(match[1]));
            if (!chat) return reply({ error: 'Chat not found' }, 404);
            const suffix = match[2];
            if (!suffix && method === 'GET') {
                if (delayChat === chat.id) { delayChat = null; await new Promise(resolve => setTimeout(resolve, 500)); }
                return reply(chat);
            }
            if (!suffix && method === 'DELETE') { deletes++; chats.splice(chats.indexOf(chat), 1); return reply({}); }
            if (suffix === '/title') { chat.title = req.postDataJSON().title; return reply({}); }
            if (suffix === '/upload') {
                await new Promise(resolve => setTimeout(resolve, 200));
                const name = req.postDataBuffer().toString().match(/filename="([^"]+)"/)?.[1] || 'notes.txt';
                chat.files.push({ id: ++uploads, original_name: name }); return reply({ filename: name });
            }
            if (suffix === '/files/content') return reply({ previewable: true, content: 'First paragraph and original notes.' });
            if (/^\/files\/\d+$/.test(suffix)) { deletes++; chat.files = chat.files.filter(file => file.id !== Number(suffix.split('/').pop())); return reply({}); }
            if (suffix === '/ask' || /^\/messages\/\d+$/.test(suffix)) {
                askCount++;
                if (rejectNextQuestion) { rejectNextQuestion = false; return reply({ error: 'Please try a different question.' }, 422); }
                await new Promise(resolve => setTimeout(resolve, 150));
                let message = suffix === '/ask' ? null : chat.messages.find(item => item.id === Number(suffix.split('/').pop()));
                if (!message) { message = { id: ++nextMessage }; chat.messages.push(message); }
                Object.assign(message, { question: req.postDataJSON().question, answer: null, citations: [] });
                const queryId = `query_${askCount}`;
                pending.set(queryId, { message });
                return reply({ query_id: queryId, message_id: message.id });
            }
            return reply({ error: 'Unexpected endpoint' }, 404);
        });
        const select = async id => {
            await page.locator(`[data-action="select"][data-id="${id}"]`).click();
            await page.waitForFunction(id => document.querySelector('[data-action="select"][aria-pressed="true"]')?.dataset.id === String(id)
                && document.querySelector('#messages-container').getAttribute('aria-busy') === 'false', id);
        };
        const composer = page.locator('#question-input'), send = page.locator('#send-question');
        await page.goto(url, { waitUntil: 'networkidle' });
        assert.ok(await page.getByRole('button', { name: 'Start a conversation' }).isVisible());
        await page.getByRole('button', { name: 'Start a conversation' }).click();
        await page.waitForFunction(() => document.querySelector('#chat-title').textContent === 'New Chat');
        await composer.fill('A question before uploading');
        assert.ok(await send.isDisabled());
        await page.locator('#file-input').setInputFiles({ name: 'bad.exe', mimeType: 'application/octet-stream', buffer: Buffer.from('not a document') });
        await page.waitForFunction(() => document.querySelector('#notice-message').textContent.includes('supported document'));
        assert.equal(uploads, 0);
        await page.locator('#file-input').setInputFiles([
            { name: 'notes.txt', mimeType: 'text/plain', buffer: Buffer.from('Some notes') },
            { name: "client's-notes.txt", mimeType: 'text/plain', buffer: Buffer.from('More notes') },
        ]);
        await page.locator('#upload-status').filter({ hasText: 'Uploading' }).waitFor();
        await page.waitForFunction(() => document.querySelectorAll('.file-tag').length === 2);
        await page.waitForFunction(() => !document.querySelector('#question-input').disabled);
        assert.equal(await composer.inputValue(), 'A question before uploading');
        await page.getByRole('button', { name: 'Summarize the key points.', exact: true }).click();
        assert.equal(await composer.inputValue(), 'Summarize the key points.');
        checks.push('onboarding, file validation, multiple uploads, prompt suggestions');

        await select(101); await composer.fill('Draft A');
        await select(102); await composer.fill('Draft B');
        await select(101); assert.equal(await composer.inputValue(), 'Draft A');
        await page.reload({ waitUntil: 'networkidle' });
        await page.waitForFunction(() => !document.querySelector('#question-input').disabled);
        assert.equal(await composer.inputValue(), 'Draft A');
        await page.locator('#chat-search').fill('client');
        assert.equal(await page.locator('.chat-item').count(), 1);
        await page.locator('#chat-search').fill('');
        delayChat = 102;
        await page.locator('[data-action="select"][data-id="102"]').click();
        await select(101);
        await page.waitForTimeout(600);
        assert.equal(await page.locator('#chat-title').textContent(), 'Research plan');
        checks.push('per-chat drafts, refresh recovery, search, rapid switching');

        await page.locator('[data-action="rename"][data-id="102"]').click();
        assert.equal(await page.locator('#rename-input').inputValue(), "Client's notes");
        await page.locator('#rename-input').fill('Client research');
        await page.locator('#rename-save').click();
        await page.waitForFunction(() => !document.querySelector('#rename-modal').open);
        assert.equal(await page.locator('#chat-title').textContent(), 'Research plan');
        await page.locator('[data-action="delete-chat"][data-id="102"]').click();
        assert.ok(await page.locator('#confirm-cancel').evaluate(el => el === document.activeElement));
        await page.keyboard.press('Tab');
        assert.ok(await page.locator('#confirm-dialog').evaluate(el => el.contains(document.activeElement)));
        await page.keyboard.press('Escape');
        assert.equal(deletes, 0);
        checks.push('rename without switching, accessible confirmation and cancel');

        await select(103);
        await composer.fill('Keep this on failure');
        rejectNextQuestion = true;
        await send.click();
        await page.waitForFunction(() => document.querySelector('#notice-message').textContent === 'Please try a different question.');
        assert.equal(await composer.inputValue(), 'Keep this on failure');
        await page.waitForFunction(() => !document.querySelector('#send-question').disabled);
        const beforeSubmit = askCount;
        await page.evaluate(() => { window.askQuestion(); window.askQuestion(); });
        await page.waitForFunction(() => document.querySelector('.answer-status')?.textContent.includes('Preparing your answer'));
        assert.equal(askCount, beforeSubmit + 1);
        await composer.fill('My next question');
        assert.ok(await send.isDisabled());
        await select(101);
        await page.reload({ waitUntil: 'networkidle' });
        complete = true;
        await page.getByRole('button', { name: 'View answer', exact: true }).waitFor();
        await page.getByRole('button', { name: 'View answer', exact: true }).click();
        await page.locator('.answer-text').waitFor();
        assert.equal(await page.locator('.answer-text').textContent(), answer);
        assert.equal(await page.locator('.answer-text img').count(), 0);
        assert.equal(await composer.inputValue(), 'My next question');
        checks.push('failed submission preserves draft, duplicate prevention, background answers survive refresh, safe readable answer');

        await page.locator('.source-item').click();
        await page.waitForFunction(() => document.querySelector('#file-content').textContent.includes('original notes'));
        await page.keyboard.press('Escape');
        assert.equal(await page.locator('#file-viewer-modal').evaluate(el => el.open), false);
        complete = false; failAnswer = true;
        await send.click();
        await page.getByRole('button', { name: 'Retry question' }).waitFor();
        failAnswer = false;
        await page.getByRole('button', { name: 'Retry question' }).click();
        await page.waitForFunction(() => document.querySelector('.answer-status')?.textContent.includes('Preparing your answer'));
        await page.evaluate(() => {
            const jobs = JSON.parse(sessionStorage.getItem('docqa:pending'));
            for (const job of Object.values(jobs)) job.startedAt = Date.now() - 7 * 60 * 1000;
            sessionStorage.setItem('docqa:pending', JSON.stringify(jobs));
        });
        await page.reload({ waitUntil: 'networkidle' });
        await page.getByRole('button', { name: 'Check again' }).waitFor();
        const beforeCheck = askCount;
        complete = true;
        await page.getByRole('button', { name: 'Check again' }).click();
        await page.waitForFunction(() => !document.querySelector('.answer-status'));
        assert.equal(askCount, beforeCheck);
        // A saved answer must unlock the composer even if its status cache expired.
        await page.evaluate(() => {
            sessionStorage.setItem('docqa:pending', JSON.stringify({ 103: {
                queryId: 'expired_status', messageId: 202, status: 'waiting', startedAt: Date.now(),
            } }));
        });
        await page.reload({ waitUntil: 'networkidle' });
        await page.waitForFunction(() => !document.querySelector('#question-input').disabled);
        assert.deepEqual(await page.evaluate(() => JSON.parse(sessionStorage.getItem('docqa:pending'))), {});
        checks.push('source preview, model error recovery, bounded polling without duplicate resubmission');

        await page.locator('[data-action="delete-file"]').first().click();
        await page.getByRole('button', { name: 'Keep it', exact: true }).click();
        assert.equal(deletes, 0);
        await page.locator('[data-action="delete-file"]').first().click();
        await page.locator('#confirm-accept').click();
        await page.waitForFunction(() => document.querySelectorAll('.file-tag').length === 1);
        assert.equal(deletes, 1);
        await page.evaluate(() => {
            const transfer = new DataTransfer();
            transfer.items.add(new File(['Dropped document'], 'dropped.txt', { type: 'text/plain' }));
            document.querySelector('#main-content').dispatchEvent(new DragEvent('drop', { bubbles: true, dataTransfer: transfer }));
        });
        await page.waitForFunction(() => document.querySelectorAll('.file-tag').length === 2);
        checks.push('explicit removal confirmation and drag/drop');
        await page.screenshot({ path: 'storage/framework/testing/ux-desktop.png', fullPage: true, animations: 'disabled' });
        for (const width of [768, 390, 320]) {
            await page.setViewportSize({ width, height: 900 });
            const dimensions = await page.evaluate(() => ({ width: document.documentElement.scrollWidth, composer: document.querySelector('#question-input').getBoundingClientRect().width }));
            assert.ok(dimensions.width <= width, `Page overflow at ${width}`);
            assert.ok(dimensions.composer > 150, `Composer too small at ${width}`);
            if (width === 390) await page.screenshot({ path: 'storage/framework/testing/ux-mobile.png', fullPage: true, animations: 'disabled' });
        }
        await page.waitForFunction(() => document.querySelectorAll('[data-lucide]:not(svg)').length === 0);
        assert.deepEqual(errors, []);
        assert.deepEqual(dialogs, []);
        console.log(JSON.stringify({ status: 'passed', checks, browserErrors: errors, blockingDialogs: dialogs }, null, 2));
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
