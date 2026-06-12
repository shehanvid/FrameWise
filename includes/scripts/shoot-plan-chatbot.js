const chatHistory = [];

function sendSuggestion(btn) {
    document.getElementById('sp-chat-input').value = btn.textContent;
    document.getElementById('sp-suggestions').style.display = 'none';
    sendChatMessage();
}




function appendMsg(role, html) {
    const wrap   = document.getElementById('sp-chat-messages');
    const div    = document.createElement('div');
    div.className = 'sp-msg ' + role;

    const av      = document.createElement('div');
    av.className  = 'sp-msg-avatar';

    if (role === 'ai') {
        av.innerHTML = `
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
            </svg>`;
    } else {

        av.textContent = USER_INITIAL;
    }

    const bubble      = document.createElement('div');
    bubble.className  = 'sp-msg-bubble';
    bubble.innerHTML  = html;

    div.appendChild(av);
    div.appendChild(bubble);
    wrap.appendChild(div);
    wrap.scrollTop = wrap.scrollHeight;

    return bubble;
}

function appendTyping() {
    const wrap  = document.getElementById('sp-chat-messages');
    const div   = document.createElement('div');
    div.className = 'sp-msg ai';
    div.id        = 'sp-typing-indicator';

    const av     = document.createElement('div');
    av.className = 'sp-msg-avatar';
    av.innerHTML = `
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
        </svg>`;

    const bubble      = document.createElement('div');
    bubble.className  = 'sp-msg-bubble';
    bubble.innerHTML  = `<div class="sp-typing"><span></span><span></span><span></span></div>`;

    div.appendChild(av);
    div.appendChild(bubble);
    wrap.appendChild(div);
    wrap.scrollTop = wrap.scrollHeight;
}




async function sendChatMessage() {
    const input = document.getElementById('sp-chat-input');
    const btn   = document.getElementById('sp-send-btn');
    const text  = input.value.trim();
    if (!text) return;

    input.value      = '';
    input.style.height = 'auto';
    btn.disabled     = true;

    appendMsg('user', text.replace(/</g, '&lt;').replace(/>/g, '&gt;'));
    chatHistory.push({ role: 'user', content: text });
    appendTyping();

    try {
        const resp = await fetch('chatbot.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ messages: chatHistory, context: SHOOT_CONTEXT }),
        });

        const raw = await resp.text();
        let data;
        try {
            data = JSON.parse(raw);
        } catch (parseErr) {
            document.getElementById('sp-typing-indicator')?.remove();
            appendMsg('ai', 'Parse error — server returned: ' + raw.substring(0, 200));
            btn.disabled = false;
            input.focus();
            return;
        }

        const reply = data.reply || data.error || 'No response generated.';
        document.getElementById('sp-typing-indicator')?.remove();
        chatHistory.push({ role: 'assistant', content: reply });


        const formatted = reply
            .replace(/</g, '&lt;')
            .replace(/\n/g, '<br>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

        appendMsg('ai', formatted);

    } catch (e) {
        document.getElementById('sp-typing-indicator')?.remove();
        appendMsg('ai', 'Connection error: ' + e.message);
    }

    btn.disabled = false;
    input.focus();
}




async function loadDirectorTips() {


    if (TIPS_SAVED && SAVED_TIPS && SAVED_TIPS.length) return;

    const prompt = `You must respond in EXACTLY this format, nothing else:

TITLE: [3-5 word title]
TIP: [one practical sentence under 20 words]
---
TITLE: [3-5 word title]
TIP: [one practical sentence under 20 words]
---
TITLE: [3-5 word title]
TIP: [one practical sentence under 20 words]
---
TITLE: [3-5 word title]
TIP: [one practical sentence under 20 words]

Give 4 director tips for: ${SHOOT_CONTEXT.shoot_type} shoot, ${SHOOT_CONTEXT.mood} mood, at ${SHOOT_CONTEXT.location}, outfit color ${SHOOT_CONTEXT.outfit || 'not specified'}. No intro text, no explanation, just the 4 blocks above.`;

    try {
        const resp = await fetch('chatbot.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                context:  SHOOT_CONTEXT,
                messages: [{ role: 'user', content: prompt }],
            }),
        });

        const raw  = await resp.text();
        const data = JSON.parse(raw);

        if (data.error) throw new Error(data.error);


        const blocks = (data.reply || '').trim().split(/---+/).map(b => b.trim()).filter(Boolean);
        const tips   = blocks.map(block => {
            const titleMatch = block.match(/TITLE:\s*(.+)/i);
            const tipMatch   = block.match(/TIP:\s*(.+)/i);
            return {
                title: titleMatch ? titleMatch[1].trim() : '',
                text:  tipMatch   ? tipMatch[1].trim()   : block.replace(/TITLE:.*/i, '').trim(),
            };
        }).filter(t => t.title || t.text).slice(0, 4);

        if (!tips.length) throw new Error('No tips parsed');


        document.getElementById('tips-body').innerHTML = tips.map(t => `
            <div class="sp-tip">
                <div class="sp-tip-icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    </svg>
                </div>
                <div>
                    <div class="sp-tip-title">${t.title.replace(/</g, '&lt;')}</div>
                    <div class="sp-tip-text">${t.text.replace(/</g, '&lt;')}</div>
                </div>
            </div>
        `).join('');


        fetch('save-conditions.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                result_id:     RESULT_ID,
                director_tips: JSON.stringify(tips),
            }),
        });

    } catch (e) {
        console.error('[Tips]', e.message);
        document.getElementById('tips-body').innerHTML =
            '<div style="color:#6b7280;font-size:12px;padding:12px 0;">Tips unavailable.</div>';
    }
}


loadDirectorTips();