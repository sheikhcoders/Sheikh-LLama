#!/bin/bash

# Start Python inference server in background
echo "Starting inference server..."
python3 inference_server.py > inference_server.log 2>&1 &
SERVER_PID=$!

# Wait for server to start
echo "Waiting for server to load model (this may take a few seconds)..."
max_attempts=30
attempt=0
while ! curl -s http://127.0.0.1:5000/docs > /dev/null; do
    sleep 2
    attempt=$((attempt + 1))
    if [ $attempt -ge $max_attempts ]; then
        echo "Server failed to start."
        kill $SERVER_PID
        exit 1
    fi
done
echo "Server started."

# Start PHP API server
echo "Starting PHP API server..."
php -S 127.0.0.1:8001 api.php > php_server.log 2>&1 &
PHP_PID=$!
sleep 2

# Test /health
echo "Testing /health..."
curl -s http://127.0.0.1:8001/health
echo -e "\n"

# Test /info
echo "Testing /info..."
curl -s http://127.0.0.1:8001/info
echo -e "\n"

# Test /generate
echo "Testing /generate..."
curl -s -X POST http://127.0.0.1:8001/generate -d '{"prompt": "<bos> বাংলাদেশের রাজধানী ", "max_new_tokens": 10}'
echo -e "\n"

# Test thinking
echo "Testing /generate with thinking..."
curl -s -X POST http://127.0.0.1:8001/generate -d '{"prompt": "<bos><think>আমি এখন চিন্তা করছি ", "max_new_tokens": 20}'
echo -e "\n"

# Cleanup
echo "Cleaning up..."
kill $SERVER_PID
kill $PHP_PID
echo "Done."
