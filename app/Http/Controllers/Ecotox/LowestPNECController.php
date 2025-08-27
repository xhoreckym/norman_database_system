<?php

namespace App\Http\Controllers\Ecotox;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ecotox\LowestPNEC;
use App\Models\Ecotox\LowestPNECMain;
use App\Models\Ecotox\PNEC3;
use App\Models\Susdat\Substance;
use App\Models\DatabaseEntity;
use App\Models\Backend\QueryLog;
use Illuminate\Support\Facades\Auth;

class LowestPNECController extends Controller
{
    /**
    * Display a listing of the LowestPNEC resources.
    */
    public function index()
    {
        $lowestPnecs = LowestPNEC::with('substance')
        ->orderBy('id')
        ->paginate(50);
        
        return view('ecotox.lowestpnec.index', [
            'lowestPnecs' => $lowestPnecs,
            'displayOption' => 0,
        ]);
    }
    

    
    /**
    * Search for LowestPNEC records based on exp_pred filter.
    */
    /**
     * AJAX endpoint for LowestPNEC data with search and filtering
     */
    public function getData(Request $request)
    {
        $perPage = $request->get('per_page', 25);
        $sortColumn = $request->get('sort', 'id');
        $sortDirection = $request->get('direction', 'asc');
        $search = $request->get('search', '');
        $expPred = $request->get('exp_pred', '');
        
        $query = LowestPNEC::with('substance');
        
        // Apply substance name search (only within substances that exist in LowestPNEC table)
        if (!empty(trim($search))) {
            $query->whereHas('substance', function($subQuery) use ($search) {
                $subQuery->where('name', 'ILIKE', '%' . trim($search) . '%');
            });
        }
        
        // Apply exp_pred filter (experimental vs predicted)
        // Database values: 1 = Experimental, 2 = Predicted
        if (!empty($expPred)) {
            $query->where('lowest_exp_pred', (int) $expPred);
        }
        
        // Apply sorting
        $allowedSortColumns = ['id', 'sus_id', 'substance_id', 'lowest_exp_pred'];
        if (in_array($sortColumn, $allowedSortColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->orderBy('id', 'asc');
        }
        
        $results = $query->paginate($perPage);
        
        return response()->json($results);
    }

    public function search(Request $request)
    {
        $searchParameters = [];
        $resultsObjects = LowestPNEC::with('substance');
        
        // Apply substance name filter
        if ($request->has('substance_name') && trim($request->substance_name) !== '') {
            $substanceName = trim($request->substance_name);
            $resultsObjects = $resultsObjects->whereHas('substance', function($query) use ($substanceName) {
                $query->where('name', 'ILIKE', '%' . $substanceName . '%');
            });
            $searchParameters['Substance Name'] = $substanceName;
        }
        
        // Apply exp_pred filter (experimental vs predicted)
        // Database values: 1 = Experimental, 2 = Predicted
        if ($request->has('exp_pred') && $request->exp_pred !== '') {
            $expPredValue = (int) $request->exp_pred;
            $resultsObjects = $resultsObjects->where('lowest_exp_pred', $expPredValue);
            $searchParameters['Data Type'] = $expPredValue == 1 ? 'Experimental' : 'Predicted';
        }
        
        // Order and paginate results
        $resultsObjects = $resultsObjects->orderBy('id', 'asc')
            ->paginate(50)
            ->withQueryString();
        
        return view('ecotox.lowestpnec.index', [
            'lowestPnecs' => $resultsObjects,
            'searchParameters' => $searchParameters,
            'request' => $request,
            'displayOption' => 0,
        ]);
    }
    
    /**
    * Display the specified resource as JSON.
    */
    public function show($id)
    {
        $lowestPnec = LowestPNEC::with('substance')->findOrFail($id);
        
        // Find the related LowestPNECMain record if exists
        $lowestPnecMain = LowestPNECMain::with(['substance', 'editor'])
        ->where('sus_id', $lowestPnec->sus_id)
        ->first();
        
        // Prepare the response data
        $responseData = $lowestPnec->toArray();
        
        // Add the main record data if available
        if ($lowestPnecMain) {
            $responseData['main_record'] = $lowestPnecMain->toArray();
            
            // If editor info is available, include it
            if ($lowestPnecMain->editor) {
                $responseData['editor'] = [
                    'id' => $lowestPnecMain->editor->id,
                    'name' => $lowestPnecMain->editor->name,
                ];
            }
            
            // Look up PNEC3 record if we have the base_id
            if ($lowestPnecMain->lowest_base_id) {
                $pnec3 = PNEC3::where('norman_pnec_id', $lowestPnecMain->lowest_base_id)->first();
                if ($pnec3) {
                    $responseData['pnec3'] = $pnec3->toArray();
                }
            }
        }
        
        return response()->json($responseData);
    }
    
    public function countAll(){
        DatabaseEntity::where('code', 'ecotox.pnec')->update([
            'last_update' => LowestPNEC::max('updated_at'),
            'number_of_records' => LowestPNEC::count()
        ]);
        session()->flash('success', 'Database counts updated successfully');
        return redirect()->back();
    }
    
    
}