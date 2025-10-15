<?php

namespace App\Http\Controllers\Literature;

use App\Http\Controllers\Controller;
use App\Models\Literature\BiotaSex;
use Illuminate\Http\Request;

class BiotaSexController extends Controller
{
    public function index()
    {
        $biotaSexs = BiotaSex::orderBy('id')->paginate(25);
        return view('literature.biota_sexs.index', compact('biotaSexs'));
    }

    public function download()
    {
        $biotaSexs = BiotaSex::orderBy('id')->get();

        $filename = 'biota_sexs_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($biotaSexs) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, ['id', 'name']);

            // Add data rows
            foreach ($biotaSexs as $item) {
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
        $biotaSex = new BiotaSex();
        $isCreate = true;
        return view('literature.biota_sexs.upsert', compact('biotaSex', 'isCreate'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_biota_sexs,name',
        ]);

        BiotaSex::create($validated);

        return redirect()->route('literature.biota_sexs.index')
            ->with('success', 'Biota sex created successfully.');
    }

    public function edit(BiotaSex $biotaSex)
    {
        $isCreate = false;
        return view('literature.biota_sexs.upsert', compact('biotaSex', 'isCreate'));
    }

    public function update(Request $request, BiotaSex $biotaSex)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_biota_sexs,name,' . $biotaSex->id,
        ]);

        $biotaSex->update($validated);

        return redirect()->route('literature.biota_sexs.index')
            ->with('success', 'Biota sex updated successfully.');
    }

}
