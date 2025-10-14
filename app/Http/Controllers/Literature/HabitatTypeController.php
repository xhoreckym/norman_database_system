<?php

namespace App\Http\Controllers\Literature;

use App\Http\Controllers\Controller;
use App\Models\Literature\HabitatType;
use Illuminate\Http\Request;

class HabitatTypeController extends Controller
{
    public function index()
    {
        $habitatTypes = HabitatType::orderBy('id')->paginate(25);
        return view('literature.habitat_types.index', compact('habitatTypes'));
    }

    public function download()
    {
        $habitatTypes = HabitatType::orderBy('id')->get();

        $filename = 'habitat_types_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($habitatTypes) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, ['id', 'name']);

            // Add data rows
            foreach ($habitatTypes as $item) {
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
        $habitatType = new HabitatType();
        $isCreate = true;
        return view('literature.habitat_types.upsert', compact('habitatType', 'isCreate'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_habitat_types,name',
        ]);

        HabitatType::create($validated);

        return redirect()->route('literature.habitat_types.index')
            ->with('success', 'Habitat type created successfully.');
    }

    public function edit(HabitatType $habitatType)
    {
        $isCreate = false;
        return view('literature.habitat_types.upsert', compact('habitatType', 'isCreate'));
    }

    public function update(Request $request, HabitatType $habitatType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_habitat_types,name,' . $habitatType->id,
        ]);

        $habitatType->update($validated);

        return redirect()->route('literature.habitat_types.index')
            ->with('success', 'Habitat type updated successfully.');
    }

}
