<?php

namespace App\Http\Controllers\Factsheet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Susdat\Substance;
use App\Models\Factsheet\FactsheetEntity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FactsheetController extends Controller
{
    public function index()
    {
        return view('factsheet.index');
    }

    public function filter(Request $request)
    {
        // Get search parameters from request
        $search = $request->get('search', '');
        $searchType = $request->get('searchType', 'name');
        $substances = $request->get('substances', []);
        
        // Convert to array if it's a single value, but ensure only one substance
        if (!is_array($substances)) {
            $substances = $substances ? [$substances] : [];
        }
        
        // If multiple substances are selected, take only the first one
        if (count($substances) > 1) {
            $substances = [reset($substances)];
        }
        
        return view('factsheet.filter', compact('request', 'search', 'searchType', 'substances'));
    }

    public function search(Request $request)
    {
        // Get selected substance from the request - only allow single substance
        $substanceId = $request->get('substances');
        
        // If substances is an array, take only the first one
        if (is_array($substanceId)) {
            $substanceId = !empty($substanceId) ? $substanceId[0] : null;
        }
        
        // If no substance selected, redirect back to filter
        if (empty($substanceId)) {
            return redirect()->route('factsheets.search.filter')
                ->with('error', 'Please select exactly one substance to view its factsheet.');
        }
        
        // Fetch the single substance
        $substance = Substance::find($substanceId);
        
        if (!$substance) {
            return redirect()->route('factsheets.search.filter')
                ->with('error', 'Selected substance not found.');
        }
        
        // Get factsheet entities for display and process their data
        $factsheetEntities = FactsheetEntity::ordered()->get();
        
        // Process each entity to prepare presentation data
        foreach ($factsheetEntities as $entity) {
            if (isset($entity->data['method_of_presentation'])) {
                if ($entity->data['method_of_presentation'] === 'database_table') {
                    // CASE 1: Database table presentation
                    $entity->processed_data = $this->processDatabaseTableData($entity, $substance);
                } elseif ($entity->data['method_of_presentation'] === 'text') {
                    // CASE 2: Text presentation
                    $entity->processed_data = $this->processTextData($entity);
                }
            }
        }
        
        return view('factsheet.index', compact('substance', 'factsheetEntities'));
    }

    /**
     * Process database table data for CASE 1: method_of_presentation = database_table
     * 
     * @param FactsheetEntity $entity
     * @param Substance $substance
     * @return array
     */
    private function processDatabaseTableData($entity, $substance): array
    {
        $processedData = [
            'type' => 'database_table',
            'model' => $entity->data['model'] ?? null,
            'fields' => $entity->data['fields'] ?? [],
            'key_value_data' => []
        ];

        // Get the model class from the entity data
        $modelClass = $entity->data['model'] ?? null;
        $fields = $entity->data['fields'] ?? [];

        if ($modelClass && class_exists($modelClass) && !empty($fields)) {
            try {
                // For substance-related models, we'll query by substance_id or direct access
                if ($modelClass === 'App\Models\Susdat\Substance') {
                    // Direct access to substance data
                    foreach ($fields as $field) {
                        $value = $substance->{$field} ?? 'N/A';
                        $processedData['key_value_data'][$field] = $value;
                    }
                } else {
                    // For other models, try to find related records
                    $model = new $modelClass();
                    
                    // Check if the model has a substance_id or similar field
                    if (in_array('substance_id', $model->getFillable()) || 
                        method_exists($model, 'substance')) {
                        $records = $model::where('substance_id', $substance->id)->get();
                        
                        if ($records->isNotEmpty()) {
                            foreach ($fields as $field) {
                                $values = $records->pluck($field)->filter()->unique();
                                $processedData['key_value_data'][$field] = $values->isNotEmpty() ? 
                                    $values->join(', ') : 'N/A';
                            }
                        } else {
                            // No records found
                            foreach ($fields as $field) {
                                $processedData['key_value_data'][$field] = 'No data available';
                            }
                        }
                    } else {
                        // Model doesn't have substance relation, return placeholder
                        foreach ($fields as $field) {
                            $processedData['key_value_data'][$field] = 'Model relation not configured';
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error processing database table data: ' . $e->getMessage());
                foreach ($fields as $field) {
                    $processedData['key_value_data'][$field] = 'Error loading data';
                }
            }
        }

        return $processedData;
    }

    /**
     * Process text data for CASE 2: method_of_presentation = text
     * 
     * @param FactsheetEntity $entity
     * @return array
     */
    private function processTextData($entity): array
    {
        return [
            'type' => 'text',
            'content' => $entity->data['text'] ?? 'No text content available'
        ];
    }

}
