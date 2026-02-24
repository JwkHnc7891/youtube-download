<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$command = isset($data['command']) ? trim($data['command']) : '';

if (empty($command)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No command provided']);
    exit;
}

// Sanitize: strip any shell meta-characters we don't expect
// Allow: letters, numbers, spaces, hyphens, underscores, dots, slashes (fwd+back),
//        colons, quotes, parens, brackets, percent-signs (template vars), commas, plus, asterisk, equals, @
if (preg_match('/[`$\|;&<>]/', $command)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Command contains disallowed characters']);
    exit;
}

// Escape double-quotes inside the command so it is safe inside cmd /k "..."
$escaped = str_replace('"', '\"', $command);

// Open a new cmd.exe window that stays open after running (cmd /k)
// The outer double-quotes around the whole cmd /k "..." block are required by cmd.exe
$launch = 'start "" cmd /k "' . $escaped . '"';

// popen with 'r' opens the process without waiting
pclose(popen($launch, 'r'));

echo json_encode(['success' => true]);
