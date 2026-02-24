<?php

declare(strict_types=1);

namespace Database\Seeders\EmpodatSuspect\Traits;

use Illuminate\Support\Facades\DB;

trait LoadsSubstanceCaches
{
    /**
     * Cache for substance lookups: code => substance_id
     * Contains both new codes (from susdat_substances) and old codes (via mapping table)
     */
    protected array $substanceCache = [];

    /**
     * Cache for station mapping lookups: xlsx_name => ['mapping_id' => id, 'station_id' => id]
     */
    protected array $stationMappingCache = [];

    /**
     * Cache for station id lookups
     */
    protected array $stationIdCache = [];

    /**
     * Load all lookup tables into memory for faster processing.
     * Builds a unified substance cache that resolves both new and old (legacy) codes.
     */
    protected function loadLookupCaches(): void
    {
        $this->loadSubstanceCache();
        $this->loadStationMappingCache();
    }

    /**
     * Load substances by code and include legacy code mappings.
     * All codes are normalized (leading zeros stripped) for consistent lookups.
     */
    protected function loadSubstanceCache(): void
    {
        // First, load all substances by their current (new) code
        // Normalize codes by stripping leading zeros for consistent lookups
        $substances = DB::table('susdat_substances')
            ->whereNotNull('code')
            ->select('id', 'code')
            ->get();

        foreach ($substances as $s) {
            $normalizedCode = $this->normalizeCode($s->code);
            $this->substanceCache[$normalizedCode] = $s->id;
        }

        $this->command->info('Loaded '.count($this->substanceCache).' substances');

        // Now load legacy code mappings and add them to the cache
        // This allows old codes to resolve to the same substance_id as their new codes
        $mappings = DB::table('empodat_suspect_susdat_code_mappings')
            ->select('old_code', 'new_code')
            ->get();

        $mappedCount = 0;
        $unmappedNewCodes = [];

        foreach ($mappings as $mapping) {
            $normalizedOldCode = $this->normalizeCode($mapping->old_code);
            $normalizedNewCode = $this->normalizeCode($mapping->new_code);

            // Only add the mapping if the new_code exists in our substance cache
            if (isset($this->substanceCache[$normalizedNewCode])) {
                $this->substanceCache[$normalizedOldCode] = $this->substanceCache[$normalizedNewCode];
                $mappedCount++;
            } else {
                $unmappedNewCodes[] = $mapping->new_code;
            }
        }

        $this->command->info("Loaded {$mappedCount} legacy code mappings");

        if (count($unmappedNewCodes) > 0) {
            $this->command->warn('Warning: '.count($unmappedNewCodes).' mapping entries have new_code not found in susdat_substances');
            if (count($unmappedNewCodes) <= 10) {
                $this->command->warn('Missing new_codes: '.implode(', ', $unmappedNewCodes));
            }
        }
    }

    /**
     * Normalize a code by stripping leading zeros.
     * "00000008" -> "8", "00098776" -> "98776"
     */
    protected function normalizeCode(string $code): string
    {
        // Remove leading zeros, but keep at least one digit
        return ltrim($code, '0') ?: '0';
    }

    /**
     * Load station mapping with station_id.
     */
    protected function loadStationMappingCache(): void
    {
        $mappings = DB::table('empodat_suspect_xlsx_stations_mapping')
            ->select('id', 'xlsx_name', 'station_id')
            ->get();

        foreach ($mappings as $m) {
            $this->stationMappingCache[$m->xlsx_name] = [
                'mapping_id' => $m->id,
                'station_id' => $m->station_id,
            ];
        }

        $this->command->info('Loaded '.count($this->stationMappingCache).' station mappings');
    }

    /**
     * Resolve a substance code (old or new) to a substance_id.
     * The code is normalized (leading zeros stripped) before lookup.
     *
     * @param  string|null  $code  The substance code to resolve
     * @return int|null The substance_id, or null if not found
     */
    protected function resolveSubstanceId(?string $code): ?int
    {
        if ($code === null) {
            return null;
        }

        $normalizedCode = $this->normalizeCode($code);

        return $this->substanceCache[$normalizedCode] ?? null;
    }

    /**
     * Validate that all substance_ids inserted for a given file_id
     * actually exist in susdat_substances. Runs a single query after
     * seeding completes — no impact on insert performance.
     */
    protected function validateSubstanceIds(int $fileId): void
    {
        $orphans = DB::table('empodat_suspect_main')
            ->where('file_id', $fileId)
            ->whereNotNull('substance_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('susdat_substances')
                    ->whereColumn('susdat_substances.id', 'empodat_suspect_main.substance_id');
            })
            ->select('substance_id', DB::raw('COUNT(*) as records'))
            ->groupBy('substance_id')
            ->get();

        if ($orphans->isEmpty()) {
            $this->command->info('Substance ID validation passed: all substance_ids are valid.');

            return;
        }

        $totalRecords = $orphans->sum('records');
        $this->command->error("Substance ID validation FAILED: {$orphans->count()} orphaned substance_id(s) affecting {$totalRecords} records.");

        foreach ($orphans as $orphan) {
            $this->command->error("  substance_id={$orphan->substance_id} ({$orphan->records} records)");
        }
    }
}
