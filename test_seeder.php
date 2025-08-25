<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ecotox\EcotoxCredQuestion;

echo "Testing EcotoxCredQuestionSeeder...\n";

// Check current records
$currentCount = EcotoxCredQuestion::count();
echo "Current records in table: {$currentCount}\n";

// Run the seeder
$seeder = new \Database\Seeders\EcotoxCredQuestionSeeder();
$seeder->run();

// Check new record count
$newCount = EcotoxCredQuestion::count();
echo "New records in table: {$newCount}\n";

// Show some sample data
$mainQuestions = EcotoxCredQuestion::whereNull('parent_id')->take(3)->get();
echo "\nSample main questions:\n";
foreach ($mainQuestions as $question) {
    echo "- {$question->question_number}. {$question->question_text}\n";
    echo "  Max score: {$question->max_score}\n";
    
    $subQuestions = $question->subQuestions()->take(2)->get();
    foreach ($subQuestions as $sub) {
        echo "  {$sub->question_letter}. {$sub->question_text}\n";
    }
    echo "\n";
}

echo "Seeder test completed!\n";
