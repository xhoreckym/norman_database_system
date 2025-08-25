<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ecotox\EcotoxCredQuestion;

echo "Testing CRED Questions Table...\n";

// Check if table exists and has data
try {
    $totalQuestions = EcotoxCredQuestion::count();
    echo "Total questions in table: {$totalQuestions}\n";
    
    if ($totalQuestions > 0) {
        // Get main questions
        $mainQuestions = EcotoxCredQuestion::whereNull('parent_id')->count();
        echo "Main questions: {$mainQuestions}\n";
        
        // Get sub-questions
        $subQuestions = EcotoxCredQuestion::whereNotNull('parent_id')->count();
        echo "Sub-questions: {$subQuestions}\n";
        
        // Show sample data
        echo "\nSample main question:\n";
        $sampleMain = EcotoxCredQuestion::whereNull('parent_id')->first();
        if ($sampleMain) {
            echo "- ID: {$sampleMain->id}\n";
            echo "- Number: {$sampleMain->question_number}\n";
            echo "- Text: {$sampleMain->question_text}\n";
            echo "- Max Score: {$sampleMain->max_score}\n";
            
            // Check sub-questions
            $subs = $sampleMain->subQuestions;
            echo "- Sub-questions: " . $subs->count() . "\n";
            foreach ($subs as $sub) {
                echo "  * {$sub->question_letter}: {$sub->question_text}\n";
            }
        }
    } else {
        echo "Table is empty. You may need to run the seeder.\n";
        echo "Run: php artisan db:seed --class=EcotoxCredQuestionSeeder\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest completed!\n";
