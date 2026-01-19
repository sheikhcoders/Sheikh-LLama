<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sheikh Assistant</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .container { max-width: 800px; margin: 50px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; }
        #chat-box { height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-bottom: 20px; border-radius: 4px; display: flex; flex-direction: column; }
        .message { margin: 5px 0; padding: 10px; border-radius: 4px; max-width: 80% }
        .user { align-self: flex-end; background-color: #007bff; color: white; }
        .assistant { align-self: flex-start; background-color: #e9ecef; color: #333; }
        .thinking { font-style: italic; color: #888; font-size: 0.9em; margin-bottom: 5px; }
        .input-group { display: flex; }
        input[type="text"] { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px 0 0 4px; outline: none; }
        button { padding: 10px 20px; border: none; background-color: #28a745; color: white; border-radius: 0 4px 4px 0; cursor: pointer; }
        button:hover { background-color: #218838; }
        .status { font-size: 0.8em; color: #666; margin-top: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sheikh 4.5 Assistant</h1>
        <div id="chat-box">
            <div class="message assistant">স্বাগতম! আমি শেখ। আমি আপনাকে কীভাবে সাহায্য করতে পারি?</div>
        </div>
        <div class="input-group">
            <input type="text" id="user-input" placeholder="আপনার বার্তা লিখুন..." onkeypress="if(event.key === 'Enter') sendMessage()">
            <button onclick="sendMessage()">পাঠান</button>
        </div>
        <div class="status" id="status-text">মডেল লোড হচ্ছে...</div>
    </div>

    <script>
        async function checkStatus() {
            try {
                const response = await fetch('/health');
                const data = await response.json();
                if (data.status === 'ok') {
                    document.getElementById('status-text').innerText = 'মডেল প্রস্তুত।';
                }
            } catch (e) {
                document.getElementById('status-text').innerText = 'মডেল অফলাইন।';
            }
        }

        async function sendMessage() {
            const input = document.getElementById('user-input');
            const chatBox = document.getElementById('chat-box');
            const message = input.value.trim();
            if (!message) return;

            // Add user message
            const userDiv = document.createElement('div');
            userDiv.className = 'message user';
            userDiv.innerText = message;
            chatBox.appendChild(userDiv);
            input.value = '';
            chatBox.scrollTop = chatBox.scrollHeight;

            // Add assistant placeholder
            const assistantDiv = document.createElement('div');
            assistantDiv.className = 'message assistant';
            assistantDiv.innerText = 'চিন্তা করছি...';
            chatBox.appendChild(assistantDiv);
            chatBox.scrollTop = chatBox.scrollHeight;

            try {
                const response = await fetch('/generate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        prompt: `<bos><think>${message}</think>`,
                        max_new_tokens: 100
                    })
                });
                const data = await response.json();

                let content = '';
                if (data.thinking) {
                    content += `<div class="thinking">চিন্তা: ${data.thinking}</div>`;
                }
                content += data.answer || data.completion || 'দুঃখিত, আমি উত্তর দিতে পারছি না।';

                assistantDiv.innerHTML = content;
            } catch (e) {
                assistantDiv.innerText = 'ত্রুটি: সার্ভারের সাথে যোগাযোগ করা যাচ্ছে না।';
            }
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        checkStatus();
        setInterval(checkStatus, 10000);
    </script>
</body>
</html>
