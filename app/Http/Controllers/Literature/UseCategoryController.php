<?php

namespace App\Http\Controllers\Literature;

use App\Http\Controllers\Controller;
use App\Models\Literature\UseCategory;
use Illuminate\Http\Request;

class UseCategoryController extends Controller
{
    public function index()
    {
        $useCategories = UseCategory::orderBy('id')->paginate(25);
        return view('literature.use_categories.index', compact('useCategories'));
    }

    public function download()
    {
        $useCategories = UseCategory::orderBy('id')->get();

        $filename = 'use_categories_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($useCategories) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, ['id', 'name']);

            // Add data rows
            foreach ($useCategories as $item) {
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
        $useCategory = new UseCategory();
        $isCreate = true;
        return view('literature.use_categories.upsert', compact('useCategory', 'isCreate'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_use_categories,name',
        ]);

        UseCategory::create($validated);

        return redirect()->route('literature.use_categories.index')
            ->with('success', 'Use category created successfully.');
    }

    public function edit(UseCategory $useCategory)
    {
        $isCreate = false;
        return view('literature.use_categories.upsert', compact('useCategory', 'isCreate'));
    }

    public function update(Request $request, UseCategory $useCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:list_use_categories,name,' . $useCategory->id,
        ]);

        $useCategory->update($validated);

        return redirect()->route('literature.use_categories.index')
            ->with('success', 'Use category updated successfully.');
    }

}
