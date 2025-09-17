<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('telescope:prune --hours=72')->daily();

Schedule::command('logs:clear')->days([1, 5])->at('03:00');
