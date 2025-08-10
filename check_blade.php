<?php

$content = file_get_contents('resources/views/backend/querylog/index.blade.php');
$lines = explode("\n", $content);
$stack = [];
$lineNumber = 0;

foreach ($lines as $line) {
    $lineNumber++;
    
    // Check for inline if/endif on same line first
    if (preg_match('/@if\b.*@endif\b/', $line)) {
        // Skip - this is an inline conditional that opens and closes on same line
        continue;
    }
    
    // Check for opening tags
    if (preg_match('/@(if|foreach|switch|php)\b/', $line, $matches)) {
        $stack[] = [$matches[1], $lineNumber, $line];
    } 
    // Check for closing tags
    elseif (preg_match('/@(endif|endforeach|endswitch|endphp)\b/', $line, $matches)) {
        if (empty($stack)) {
            echo "Error: Unexpected {$matches[1]} at line $lineNumber\n";
            echo "Line: " . trim($line) . "\n";
            exit(1);
        }
        
        $last = array_pop($stack);
        $expected = [
            'if' => 'endif',
            'foreach' => 'endforeach', 
            'switch' => 'endswitch',
            'php' => 'endphp'
        ];
        
        if ($expected[$last[0]] !== $matches[1]) {
            echo "Error: Expected {$expected[$last[0]]} but found {$matches[1]} at line $lineNumber\n";
            echo "Line: " . trim($line) . "\n";
            echo "Opened at line {$last[1]}: " . trim($last[2]) . "\n";
            exit(1);
        }
    }
}

if (!empty($stack)) {
    echo "Error: Unclosed blocks:\n";
    foreach ($stack as $item) {
        echo "  {$item[0]} opened at line {$item[1]}: " . trim($item[2]) . "\n";
    }
    exit(1);
}

echo "All Blade directive blocks are properly matched\n";
