<?php

namespace App\Livewire\Factsheet;

use Livewire\Component;
use App\Models\Susdat\Substance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubstanceSearch extends Component
{
    public $search = '';
    public $searchType = 'name';
    public $selectedSubstanceIds = [];
    public $selectedSubstances = [];
    public $existingSubstances = [];
    
    public function mount($existingSubstances = [])
    {
        if(!empty($existingSubstances)) {
            $this->selectedSubstanceIds = is_array($existingSubstances) ? 
                [array_values($existingSubstances)[0]] : [$existingSubstances];
            
            $this->applySubstanceFilter();
        }
    }
    
    public function render()
    {
        $results = [];
        $resultsAvailable = false;
        
        if(strlen($this->search) > 2) {
            // Build the query for substances with factsheet data
            $query = Substance::query()
                ->select([
                    'susdat_substances.*',
                    DB::raw('(SELECT COUNT(*) FROM empodat_main WHERE substance_id = susdat_substances.id) as empodat_count'),
                    DB::raw('(SELECT COUNT(*) FROM ecotox_main_3 WHERE substance_id = susdat_substances.id) as ecotox_count'),
                    DB::raw('(SELECT COUNT(*) FROM indoor_main WHERE substance_id = susdat_substances.id) as indoor_count'),
                    DB::raw('(SELECT COUNT(*) FROM passive_sampling_main WHERE substance_id = susdat_substances.id) as passive_count')
                ])
                ->where(function($q) {
                    $q->whereRaw('(SELECT COUNT(*) FROM empodat_main WHERE substance_id = susdat_substances.id) > 0')
                      ->orWhereRaw('(SELECT COUNT(*) FROM ecotox_main_3 WHERE substance_id = susdat_substances.id) > 0')
                      ->orWhereRaw('(SELECT COUNT(*) FROM indoor_main WHERE substance_id = susdat_substances.id) > 0')
                      ->orWhereRaw('(SELECT COUNT(*) FROM passive_sampling_main WHERE substance_id = susdat_substances.id) > 0');
                });
            
            // Apply search filters
            if($this->searchType == 'cas_number') {
                $searchTerm = str_replace('-', '', $this->search);
                $query = $query->where(function($q) use ($searchTerm) {
                    $q->where('susdat_substances.cas_number', 'ilike', '%' . $this->search . '%')
                      ->orWhere(DB::raw("REPLACE(susdat_substances.cas_number, '-', '')"), 'ilike', '%' . $searchTerm . '%');
                });
            } elseif($this->searchType == 'name') {
                $query = $query->where('susdat_substances.name', 'ilike', '%' . $this->search . '%');
            } elseif($this->searchType == 'stdinchikey') {
                $query = $query->where('susdat_substances.stdinchikey', 'ilike', $this->search . '%');
            }
            
            // Order by total data count and limit
            $results = $query
                ->orderByRaw('(empodat_count + ecotox_count + indoor_count + passive_count) DESC')
                ->orderBy('susdat_substances.name', 'asc')
                ->limit(30)
                ->get();
            
            if (config('app.debug')) {
                Log::info('SubstanceSearch query returned ' . $results->count() . ' results for search: "' . $this->search . '"');
            }
            
            $resultsAvailable = $results->count() > 0;
        }
        
        return view('livewire.factsheet.substance-search', [
            'results' => $results,
            'resultsAvailable' => $resultsAvailable,
            'searchType' => $this->searchType,
            'selectedSubstances' => $this->selectedSubstances,
        ]);
    }
    
    public function applySubstanceFilter()
    {
        if (!is_array($this->selectedSubstanceIds)) {
            $this->selectedSubstanceIds = [$this->selectedSubstanceIds];
        }
        
        if (count($this->selectedSubstanceIds) > 0) {
            $substanceId = end($this->selectedSubstanceIds);
            $this->selectedSubstanceIds = [$substanceId];
            
            // Fetch substance with data counts
            $substance = Substance::query()
                ->select([
                    'susdat_substances.*',
                    DB::raw('(SELECT COUNT(*) FROM empodat_main WHERE substance_id = susdat_substances.id) as empodat_count'),
                    DB::raw('(SELECT COUNT(*) FROM ecotox_main_3 WHERE substance_id = susdat_substances.id) as ecotox_count'),
                    DB::raw('(SELECT COUNT(*) FROM indoor_main WHERE substance_id = susdat_substances.id) as indoor_count'),
                    DB::raw('(SELECT COUNT(*) FROM passive_sampling_main WHERE substance_id = susdat_substances.id) as passive_count')
                ])
                ->where('susdat_substances.id', $substanceId)
                ->first();
            
            if ($substance) {
                $totalCount = $substance->empodat_count + $substance->ecotox_count + 
                             $substance->indoor_count + $substance->passive_count;
                
                $this->selectedSubstances = [[
                    'id' => $substance->id,
                    'name' => $substance->name,
                    'cas_number' => $substance->cas_number,
                    'stdinchikey' => $substance->stdinchikey,
                    'total_records' => $totalCount,
                    'empodat_count' => $substance->empodat_count,
                    'ecotox_count' => $substance->ecotox_count,
                    'indoor_count' => $substance->indoor_count,
                    'passive_count' => $substance->passive_count
                ]];
                
                $this->dispatch('autoSubmitForm');
            } else {
                $this->selectedSubstances = [];
            }
        } else {
            $this->selectedSubstances = [];
        }
        
        $this->search = '';
    }
    
    public function removeSubstance($substanceId)
    {
        $this->selectedSubstanceIds = [];
        $this->selectedSubstances = [];
        $this->search = '';
    }
    
    public function clearFilters()
    {
        $this->search = '';
    }
}
