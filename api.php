<?php

// Simple Router
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($request_uri, PHP_URL_PATH);

if ($path === '/' || $path === '/index.php') {
    header('Content-Type: text/html; charset=UTF-8');
    include 'index.php';
    exit;
} elseif ($path === '/generate') {
    header('Content-Type: application/json');
    handleGenerate();
} elseif ($path === '/upload') {
    header('Content-Type: application/json');
    handleUpload();
} elseif ($path === '/health') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'model' => 'Sheikh-ABF']);
} elseif ($path === '/info') {
    header('Content-Type: application/json');
    $config_file = 'Sheikh-ABF/config.json';
    if (file_exists($config_file)) {
        $config = json_decode(file_get_contents($config_file), true);
        echo json_encode([
            'name' => 'Sheikh-ABF',
            'version' => '4.5',
            'creator' => 'Likhon Sheikh',
            'config' => $config
        ]);
    } else {
        echo json_encode(['error' => 'Config not found']);
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not Found', 'path' => $path]);
}

function handleGenerate() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        return;
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['prompt'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input, "prompt" is required']);
        return;
    }

    // Call Python FastAPI server
    $port = getenv('PORT') ?: '8000';
    $url = "http://127.0.0.1:$port/generate_internal";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $result = json_decode($response, true);
        if ($result) {
            if (isset($result['generated_text'])) {
                $text = $result['generated_text'];
                $prompt = $result['prompt'] ?? '';

                // Improved prompt stripping logic
                // LLMs often prepend <bos> (token 0) if not present, or it might be in the prompt already.
                // We'll try to find the prompt in the text.
                $completion = $text;
                $prompt_pos = strpos($text, $prompt);
                if ($prompt_pos !== false) {
                    $completion = substr($text, $prompt_pos + strlen($prompt));
                }

                $result['completion'] = trim($completion);

                // Handle thinking blocks
                $thinking = '';
                $answer = $text;

                if (strpos($text, '<think>') !== false && strpos($text, '</think>') !== false) {
                    $start = strpos($text, '<think>') + strlen('<think>');
                    $end = strpos($text, '</think>');
                    $thinking = substr($text, $start, $end - $start);
                    $answer = substr($text, $end + strlen('</think>'));
                } elseif (strpos($text, '<think>') !== false) {
                    $start = strpos($text, '<think>') + strlen('<think>');
                    $thinking = substr($text, $start);
                    $answer = '';
                }

                $result['thinking'] = trim($thinking);
                $result['answer'] = trim($answer);
            }
            echo json_encode($result);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to parse model server response', 'raw' => $response]);
        }
    } else {
        http_response_code($http_code ?: 500);
        echo json_encode(['error' => 'Model server error', 'details' => $response]);
    }
}

function handleUpload() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        return;
    }

    if (!isset($_FILES['image'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No image uploaded']);
        return;
    }

    $image = $_FILES['image'];
    $port = getenv('PORT') ?: '8000';
    $url = "http://127.0.0.1:$port/describe_internal";

    $cfile = new CURLFile($image['tmp_name'], $image['type'], $image['name']);
    $data = array('file' => $cfile);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        echo $response;
    } else {
        http_response_code($http_code ?: 500);
        echo json_encode(['error' => 'Vision server error', 'details' => $response]);
    }
}
