<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend\Display;

use App\Http\Controllers\Controller;
use App\Models\Backend\DisplayColumn;
use App\Models\Backend\DisplaySection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisplayColumnController extends Controller
{
    /**
     * Display columns for a specific section.
     */
    public function index(DisplaySection $section): View
    {
        $section->load(['databaseEntity', 'sectionType']);

        $columns = DisplayColumn::where('display_section_id', $section->id)
            ->orderBy('display_order')
            ->get();

        return view('backend.display.columns', compact('section', 'columns'));
    }

    /**
     * Show edit form for a column.
     */
    public function edit(DisplayColumn $column): View
    {
        $column->load(['section.databaseEntity', 'section.sectionType']);

        $formatTypes = [
            'text' => 'Text (default)',
            'number' => 'Number',
            'date' => 'Date',
            'datetime' => 'DateTime',
            'boolean' => 'Boolean (Yes/No)',
            'coordinates' => 'Coordinates',
            'json' => 'JSON',
            'link' => 'Link',
        ];

        return view('backend.display.column-edit', compact('column', 'formatTypes'));
    }

    /**
     * Update a column.
     */
    public function update(Request $request, DisplayColumn $column): RedirectResponse
    {
        $validated = $request->validate([
            'display_label' => 'nullable|string|max:500',
            'display_order' => 'required|integer|min:0',
            'is_visible' => 'boolean',
            'is_glance' => 'boolean',
            'format_type' => 'required|string|in:text,number,date,datetime,boolean,coordinates,json,link',
            'format_options' => 'nullable|json',
            'css_class' => 'nullable|string|max:255',
            'link_route' => 'nullable|string|max:255',
            'link_param' => 'nullable|string|max:255',
            'tooltip' => 'nullable|string',
        ]);

        // Convert checkbox values
        $validated['is_visible'] = $request->has('is_visible');
        $validated['is_glance'] = $request->has('is_glance');

        // Convert empty strings to null for nullable fields
        foreach (['display_label', 'css_class', 'link_route', 'link_param', 'tooltip'] as $field) {
            if (empty($validated[$field])) {
                $validated[$field] = null;
            }
        }

        // Handle format_options JSON
        if (! empty($validated['format_options'])) {
            $validated['format_options'] = json_decode($validated['format_options'], true);
        } else {
            $validated['format_options'] = null;
        }

        $column->update($validated);

        return redirect()
            ->route('backend.display.columns.index', $column->display_section_id)
            ->with('success', 'Column updated successfully.');
    }

    /**
     * Reorder columns for a section.
     */
    public function reorder(Request $request, DisplaySection $section): RedirectResponse
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:display_columns,id',
        ]);

        foreach ($validated['order'] as $position => $columnId) {
            DisplayColumn::where('id', $columnId)
                ->where('display_section_id', $section->id)
                ->update(['display_order' => ($position + 1) * 10]);
        }

        return redirect()
            ->route('backend.display.columns.index', $section->id)
            ->with('success', 'Column order updated successfully.');
    }
}
