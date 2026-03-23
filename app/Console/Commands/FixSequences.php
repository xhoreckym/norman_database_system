<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSequences extends Command
{
    protected $signature = 'db:fix-sequences';

    protected $description = 'Reset all PostgreSQL auto-increment sequences to match the current MAX(id) for every table';

    public function handle(): int
    {
        $tables = DB::select("
            SELECT table_name
            FROM information_schema.columns
            WHERE table_schema = 'public'
              AND column_name = 'id'
              AND column_default LIKE 'nextval%'
            ORDER BY table_name
        ");

        if (empty($tables)) {
            $this->info('No tables with auto-increment id columns found.');

            return self::SUCCESS;
        }

        $fixed = 0;

        foreach ($tables as $table) {
            $name = $table->table_name;
            $maxId = DB::selectOne("SELECT MAX(id) AS max_id FROM \"{$name}\"")->max_id;

            if ($maxId === null) {
                $this->line("  {$name}: empty table, skipped");

                continue;
            }

            $sequence = DB::selectOne("SELECT pg_get_serial_sequence('{$name}', 'id') AS seq")->seq;

            if ($sequence === null) {
                $this->warn("  {$name}: no sequence found, skipped");

                continue;
            }

            DB::statement("SELECT setval('{$sequence}', {$maxId})");
            $this->info("  {$name}: sequence reset to {$maxId}");
            $fixed++;
        }

        $this->newLine();
        $this->info("Done. Fixed {$fixed} sequence(s).");

        return self::SUCCESS;
    }
}
