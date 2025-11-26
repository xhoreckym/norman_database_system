<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Backend\File;
use Exception;

class FileRescanService
{
    /**
     * Database entity ID to scanner method mapping.
     * Add new scanners here as they are implemented.
     */
    protected array $scanners = [
        2 => 'scanEmpodat',           // Chemical Occurrence Data
        18 => 'scanEmpodatSuspect',   // Empodat Suspect
        // 8 => 'scanIndoor',          // Indoor Environment
        // 9 => 'scanPassive',         // Passive Sampling
        // 12 => 'scanBioassays',      // Bioassays Monitoring Data
        // 13 => 'scanSars',           // SARS-CoV-2 in sewage
        // 17 => 'scanLiterature',     // Literature
    ];

    /**
     * Rescan a file and update main_id_from, main_id_to, and number_of_records.
     *
     * @return array{success: bool, message: string, data?: array}
     */
    public function rescan(File $file): array
    {
        if (! $file->database_entity_id) {
            return [
                'success' => false,
                'message' => 'No database entity assigned to this file.',
            ];
        }

        $entityId = $file->database_entity_id;

        if (! isset($this->scanners[$entityId])) {
            return [
                'success' => false,
                'message' => 'Rescan not implemented for this database entity ('.$file->databaseEntity?->name.').',
            ];
        }

        $method = $this->scanners[$entityId];

        try {
            $result = $this->$method($file);

            $file->update([
                'main_id_from' => $result['main_id_from'],
                'main_id_to' => $result['main_id_to'],
                'number_of_records' => $result['number_of_records'],
            ]);

            return [
                'success' => true,
                'message' => 'File rescanned successfully.',
                'data' => $result,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error during rescan: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check if rescan is available for a given database entity.
     */
    public function isRescanAvailable(?int $entityId): bool
    {
        return $entityId !== null && isset($this->scanners[$entityId]);
    }

    /**
     * Get list of supported database entity IDs.
     */
    public function getSupportedEntities(): array
    {
        return array_keys($this->scanners);
    }

    /**
     * Scanner for Chemical Occurrence Data (Empodat).
     * Relation: empodat_main.file_id = files.id
     *
     * @return array{main_id_from: ?int, main_id_to: ?int, number_of_records: int}
     */
    protected function scanEmpodat(File $file): array
    {
        $stats = \Illuminate\Support\Facades\DB::table('empodat_main')
            ->where('file_id', $file->id)
            ->selectRaw('MIN(id) as min_id, MAX(id) as max_id, COUNT(*) as count')
            ->first();

        return [
            'main_id_from' => $stats->min_id,
            'main_id_to' => $stats->max_id,
            'number_of_records' => (int) $stats->count,
        ];
    }

    /**
     * Scanner for Empodat Suspect.
     * Relation: empodat_suspect_main.file_id = files.id
     *
     * @return array{main_id_from: ?int, main_id_to: ?int, number_of_records: int}
     */
    protected function scanEmpodatSuspect(File $file): array
    {
        $stats = \Illuminate\Support\Facades\DB::table('empodat_suspect_main')
            ->where('file_id', $file->id)
            ->selectRaw('MIN(id) as min_id, MAX(id) as max_id, COUNT(*) as count')
            ->first();

        return [
            'main_id_from' => $stats->min_id,
            'main_id_to' => $stats->max_id,
            'number_of_records' => (int) $stats->count,
        ];
    }
}
