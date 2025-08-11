<?php

namespace App\Http\Controllers\Susdat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Susdat\Substance;
use Illuminate\Support\Facades\DB;

class BatchConversionController extends Controller
{
    /**
     * Show the batch conversion form
     */
    public function index()
    {
        return view('susdat.batch.index');
    }

    /**
     * Process the batch conversion
     */
    public function convert(Request $request)
    {
        $request->validate([
            'identifiers' => 'required|string|max:10000',
            'input_type' => 'required|in:cas_no,substance_name,std_inchikey'
        ]);

        $identifiers = array_filter(
            array_map('trim', explode("\n", $request->identifiers)),
            function($value) { return !empty($value); }
        );

        $inputType = $request->input_type;
        $results = [];

        foreach ($identifiers as $identifier) {
            $substances = $this->findSubstance($identifier, $inputType);
            if ($substances && $substances->count() > 0) {
                foreach ($substances as $substance) {
                    $results[] = [
                        'input' => $identifier,
                        'susdat_id' => $substance->code,
                        'substance_name' => $substance->name,
                        'cas_no' => $substance->cas_number,
                        'std_inchikey' => $substance->stdinchikey,
                        'found' => true
                    ];
                }
            } else {
                $results[] = [
                    'input' => $identifier,
                    'susdat_id' => null,
                    'substance_name' => null,
                    'cas_no' => null,
                    'std_inchikey' => null,
                    'found' => false
                ];
            }
        }

        // Store form data in session for update functionality
        session([
            'batch_conversion_data' => [
                'identifiers' => $request->identifiers,
                'input_type' => $inputType
            ],
            'batch_conversion_results' => $results,
            'batch_conversion_input_type' => $inputType
        ]);

        return view('susdat.batch.results', compact('results', 'inputType'));
    }

    /**
     * Show the batch conversion form with pre-filled data from previous conversion
     */
    public function update()
    {
        $formData = session('batch_conversion_data', []);
        
        return view('susdat.batch.index', compact('formData'));
    }

    /**
     * Download results as CSV file
     */
    public function downloadCsv(Request $request)
    {
        $results = session('batch_conversion_results', []);
        
        if (empty($results)) {
            return redirect()->back()->with('error', 'No results available for download.');
        }

        $filename = 'batch_conversion_results_' . date('Y-m-d_H-i-s') . '.csv';
        
        return response()->streamDownload(function () use ($results) {
            $output = fopen('php://output', 'w');
            
            // Add header row with only specified columns
            fputcsv($output, [
                'SUSDAT ID',
                'Substance Name',
                'CAS No.',
                'StdInChIKey'
            ]);
            
            // Add data rows with only specified columns
            foreach ($results as $result) {
                if ($result['found']) {
                    fputcsv($output, [
                        'NS' . $result['susdat_id'],
                        $result['substance_name'] ?? '-',
                        $result['cas_no'] ?? '-',
                        $result['std_inchikey'] ?? '-'
                    ]);
                }
            }
            
            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Find substances based on input type and identifier
     */
    private function findSubstance($identifier, $inputType)
    {
        switch ($inputType) {
            case 'cas_no':
                return Substance::where('cas_number', $identifier)->get();
            
            case 'substance_name':
                return Substance::where('name', 'LIKE', '%' . $identifier . '%')
                    ->orWhere('name_dashboard', 'LIKE', '%' . $identifier . '%')
                    ->orWhere('name_chemspider', 'LIKE', '%' . $identifier . '%')
                    ->orWhere('name_iupac', 'LIKE', '%' . $identifier . '%')
                    ->get();
            
            case 'std_inchikey':
                return Substance::where('stdinchikey', $identifier)->get();
            
            default:
                return null;
        }
    }
}
