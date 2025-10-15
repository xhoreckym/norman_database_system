<?php

namespace App\Http\Controllers\Literature;

use App\Http\Controllers\Controller;
use App\Models\Literature\Tissue;
use Illuminate\Http\Request;

class TissueController extends Controller
{
    public function index()
    {
        $tissues = Tissue::orderBy('id')->paginate(25);
        return view('literature.tissues.index', compact('tissues'));
    }

    public function download()
    {
        $tissues = Tissue::orderBy('id')->get();

        $filename = 'tissues_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($tissues) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, ['id', 'name']);

            // Add data rows
            foreach ($tissues as $item) {
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
        $tissue = new Tissue();
        $isCreate = true;
        return view('literature.tissues.upsert', compact('tissue', 'isCreate'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_tissues,name',
        ]);

        Tissue::create($validated);

        return redirect()->route('literature.tissues.index')
            ->with('success', 'Tissue created successfully.');
    }

    public function edit(Tissue $tissue)
    {
        $isCreate = false;
        return view('literature.tissues.upsert', compact('tissue', 'isCreate'));
    }

    public function update(Request $request, Tissue $tissue)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_tissues,name,' . $tissue->id,
        ]);

        $tissue->update($validated);

        return redirect()->route('literature.tissues.index')
            ->with('success', 'Tissue updated successfully.');
    }

}
