#!/bin/bash

# Start Python main server (which proxies to PHP)
echo "Starting main server (Python + PHP)..."
export PORT=8000
python3 main.py > main_server.log 2>&1 &
SERVER_PID=$!

# Wait for server to start
echo "Waiting for server to load model and start PHP (this may take a few seconds)..."
max_attempts=30
attempt=0
while ! curl -s http://127.0.0.1:8000/health > /dev/null; do
    sleep 2
    attempt=$((attempt + 1))
    if [ $attempt -ge $max_attempts ]; then
        echo "Server failed to start."
        cat main_server.log
        kill $SERVER_PID
        exit 1
    fi
done
echo "Server started."

# Test /health
echo "Testing /health..."
curl -s http://127.0.0.1:8000/health
echo -e "\n"

# Test /info
echo "Testing /info..."
curl -s http://127.0.0.1:8000/info
echo -e "\n"

# Test /generate
echo "Testing /generate..."
curl -s -X POST http://127.0.0.1:8000/generate -d '{"prompt": "<bos> বাংলাদেশের রাজধানী ", "max_new_tokens": 10}'
echo -e "\n"

# Test thinking
echo "Testing /generate with thinking..."
curl -s -X POST http://127.0.0.1:8000/generate -d '{"prompt": "<bos><think>আমি এখন চিন্তা করছি ", "max_new_tokens": 20}'
echo -e "\n"

# Cleanup
echo "Cleaning up..."
kill $SERVER_PID
echo "Done."
