<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sheikh Assistant</title>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #00a67e;
            --bg-color: #f7f7f8;
            --text-color: #343541;
            --sidebar-color: #202123;
            --user-msg-bg: #ffffff;
            --assistant-msg-bg: #f7f7f8;
            --border-color: #d9d9e3;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Hind Siliguri', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.5;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background-color: #fff;
            padding: 1rem;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
            font-weight: 600;
            font-size: 1.2rem;
            color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        #chat-container {
            flex: 1;
            overflow-y: auto;
            padding: 2rem 0;
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }

        .message-wrapper {
            width: 100%;
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .message-wrapper.user {
            background-color: #fff;
        }

        .message-wrapper.assistant {
            background-color: var(--assistant-msg-bg);
        }

        .message-content {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            gap: 1.5rem;
            align-items: flex-start;
        }

        .avatar {
            width: 30px;
            height: 30px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #fff;
            flex-shrink: 0;
        }

        .user .avatar {
            background-color: #5436da;
        }

        .assistant .avatar {
            background-color: var(--primary-color);
        }

        .text {
            flex: 1;
            font-size: 1rem;
            word-wrap: break-word;
            white-space: pre-wrap;
        }

        .thinking-block {
            font-size: 0.9rem;
            color: #6e6e80;
            border-left: 2px solid var(--primary-color);
            padding-left: 1rem;
            margin-bottom: 0.5rem;
            font-style: italic;
        }

        footer {
            padding: 2rem 1rem;
            background-color: transparent;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }

        .input-area {
            position: relative;
            background-color: #fff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            display: flex;
            align-items: flex-end;
            padding: 0.5rem;
        }

        #user-input {
            flex: 1;
            border: none;
            padding: 0.75rem;
            font-size: 1rem;
            font-family: inherit;
            resize: none;
            max-height: 200px;
            outline: none;
            background: transparent;
        }

        #send-btn {
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: background 0.2s;
            margin-bottom: 0.25rem;
        }

        #send-btn:hover {
            background-color: #008f6c;
        }

        #send-btn:disabled {
            background-color: #ace8d9;
            cursor: not-allowed;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }

        .status-ready { background-color: #22c55e; }
        .status-busy { background-color: #eab308; }
        .status-offline { background-color: #ef4444; }

        .status-info {
            text-align: center;
            font-size: 0.75rem;
            margin-top: 0.5rem;
            color: #8e8ea0;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .message-content {
                gap: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <header>Sheikh 4.5 Assistant</header>

    <div id="chat-container">
        <div class="message-wrapper assistant">
            <div class="message-content">
                <div class="avatar">S</div>
                <div class="text">আসসালামু আলাইকুম! আমি শেখ ৪.৫। আজ আমি আপনাকে কীভাবে সাহায্য করতে পারি?</div>
            </div>
        </div>
    </div>

    <footer>
        <div class="input-area">
            <textarea id="user-input" placeholder="এখানে লিখুন..." rows="1"></textarea>
            <button id="send-btn">পাঠান</button>
        </div>
        <div class="status-info">
            <span id="status-dot" class="status-dot"></span>
            <span id="status-text">মডেল লোড হচ্ছে...</span>
        </div>
    </footer>

    <script>
        const chatContainer = document.getElementById('chat-container');
        const userInput = document.getElementById('user-input');
        const sendBtn = document.getElementById('send-btn');
        const statusDot = document.getElementById('status-dot');
        const statusText = document.getElementById('status-text');

        // Auto-resize textarea
        userInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        async function checkStatus() {
            try {
                const response = await fetch('/health');
                const data = await response.json();
                if (data.status === 'ok') {
                    statusDot.className = 'status-dot status-ready';
                    statusText.innerText = 'মডেল অনলাইন';
                }
            } catch (e) {
                statusDot.className = 'status-dot status-offline';
                statusText.innerText = 'সার্ভার অফলাইন';
            }
        }

        function appendMessage(role, text, thinking = '') {
            const wrapper = document.createElement('div');
            wrapper.className = `message-wrapper ${role}`;

            let innerHTML = `
                <div class="message-content">
                    <div class="avatar">${role === 'user' ? 'U' : 'S'}</div>
                    <div class="text">`;

            if (thinking) {
                innerHTML += `<div class="thinking-block">চিন্তা: ${thinking}</div>`;
            }

            innerHTML += `${text}</div>
                </div>`;

            wrapper.innerHTML = innerHTML;
            chatContainer.appendChild(wrapper);
            chatContainer.scrollTop = chatContainer.scrollHeight;
            return wrapper;
        }

        async function sendMessage() {
            const message = userInput.value.trim();
            if (!message || sendBtn.disabled) return;

            appendMessage('user', message);
            userInput.value = '';
            userInput.style.height = 'auto';

            sendBtn.disabled = true;
            statusDot.className = 'status-dot status-busy';
            statusText.innerText = 'শেখ চিন্তা করছে...';

            // Placeholder for assistant
            const assistantMsg = appendMessage('assistant', '...');

            try {
                const response = await fetch('/generate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        prompt: `<bos><think>${message}</think>`,
                        max_new_tokens: 256
                    })
                });
                const data = await response.json();

                const responseText = data.answer || data.completion || 'দুঃখিত, আমি উত্তর দিতে পারছি না।';
                const thinkingText = data.thinking || '';

                assistantMsg.querySelector('.text').innerHTML = (thinkingText ? `<div class="thinking-block">চিন্তা: ${thinkingText}</div>` : '') + responseText;
            } catch (e) {
                assistantMsg.querySelector('.text').innerText = 'একটি ত্রুটি ঘটেছে। অনুগ্রহ করে আবার চেষ্টা করুন।';
            } finally {
                sendBtn.disabled = false;
                checkStatus();
            }
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        sendBtn.addEventListener('click', sendMessage);
        userInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        checkStatus();
        setInterval(checkStatus, 15000);
    </script>
</body>
</html>
