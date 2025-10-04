<?php

require_once 'vendor/autoload.php';

use App\Support\CommandParser;

$parser = new CommandParser();

echo "=== TEST COMMAND PARSER ===\n";

$commands = [
    'COMPRESS whatsapp',
    'CONVERT docx',
    'OCR text',
    'SUMMARIZE short',
    'TRANSLATE fr',
    'SECURE password',
    'invalid command'
];

foreach ($commands as $command) {
    echo "\nTest: '$command'\n";
    $result = $parser->parse($command);
    if ($result) {
        echo "✅ Parsed: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ Failed to parse\n";
    }
}
