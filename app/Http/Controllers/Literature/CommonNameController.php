<?php

namespace App\Http\Controllers\Literature;

use App\Http\Controllers\Controller;
use App\Models\Literature\CommonName;
use Illuminate\Http\Request;

class CommonNameController extends Controller
{
    public function index()
    {
        $commonNames = CommonName::orderBy('id')->paginate(25);
        return view('literature.common_names.index', compact('commonNames'));
    }

    public function download()
    {
        $commonNames = CommonName::orderBy('id')->get();

        $filename = 'common_names_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($commonNames) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, ['id', 'name']);

            // Add data rows
            foreach ($commonNames as $item) {
                fputcsv($file, [
                    $item->id,
                    $item->name,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function create()
    {
        $commonName = new CommonName();
        $isCreate = true;
        return view('literature.common_names.upsert', compact('commonName', 'isCreate'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_common_names,name',
        ]);

        CommonName::create($validated);

        return redirect()->route('literature.common_names.index')
            ->with('success', 'Common name created successfully.');
    }

    public function edit(CommonName $commonName)
    {
        $isCreate = false;
        return view('literature.common_names.upsert', compact('commonName', 'isCreate'));
    }

    public function update(Request $request, CommonName $commonName)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_common_names,name,' . $commonName->id,
        ]);

        $commonName->update($validated);

        return redirect()->route('literature.common_names.index')
            ->with('success', 'Common name updated successfully.');
    }

}
