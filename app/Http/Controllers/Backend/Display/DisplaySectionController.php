<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend\Display;

use App\Http\Controllers\Controller;
use App\Models\Backend\DisplaySection;
use App\Models\Backend\DisplaySectionType;
use App\Models\DatabaseEntity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisplaySectionController extends Controller
{
    /**
     * Display list of modules with display configuration.
     */
    public function index(): View
    {
        $modules = DatabaseEntity::withCount(['displaySections'])
            ->orderBy('name')
            ->get();

        return view('backend.display.index', compact('modules'));
    }

    /**
     * Display sections for a specific module.
     */
    public function sections(string $module): View
    {
        $entity = DatabaseEntity::where('code', $module)->firstOrFail();

        $sections = DisplaySection::with(['sectionType', 'columns'])
            ->where('database_entity_id', $entity->id)
            ->orderBy('display_order')
            ->get();

        $sectionTypes = DisplaySectionType::where('is_active', true)
            ->orderBy('default_name')
            ->get();

        return view('backend.display.sections', compact('entity', 'sections', 'sectionTypes'));
    }

    /**
     * Show edit form for a section.
     */
    public function edit(DisplaySection $section): View
    {
        $section->load(['sectionType', 'databaseEntity']);

        $sectionTypes = DisplaySectionType::where('is_active', true)
            ->orderBy('default_name')
            ->get();

        return view('backend.display.section-edit', compact('section', 'sectionTypes'));
    }

    /**
     * Update a section.
     */
    public function update(Request $request, DisplaySection $section): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'section_type_id' => 'nullable|exists:display_section_types,id',
            'display_order' => 'required|integer|min:0',
            'header_bg_class' => 'nullable|string|max:100',
            'header_text_class' => 'nullable|string|max:100',
            'row_even_class' => 'nullable|string|max:100',
            'row_odd_class' => 'nullable|string|max:100',
            'row_text_class' => 'nullable|string|max:100',
            'is_visible' => 'boolean',
            'is_collapsible' => 'boolean',
            'is_collapsed_default' => 'boolean',
        ]);

        // Convert checkbox values
        $validated['is_visible'] = $request->has('is_visible');
        $validated['is_collapsible'] = $request->has('is_collapsible');
        $validated['is_collapsed_default'] = $request->has('is_collapsed_default');

        // Convert empty strings to null for nullable fields
        foreach (['name', 'header_bg_class', 'header_text_class', 'row_even_class', 'row_odd_class', 'row_text_class'] as $field) {
            if (empty($validated[$field])) {
                $validated[$field] = null;
            }
        }

        // Handle section_type_id
        $validated['section_type_id'] = $request->input('section_type_id') ?: null;

        $section->update($validated);

        return redirect()
            ->route('backend.display.sections.edit', $section->id)
            ->with('success', 'Section updated successfully.');
    }

    /**
     * Reorder sections for a module.
     */
    public function reorder(Request $request, string $module): RedirectResponse
    {
        $entity = DatabaseEntity::where('code', $module)->firstOrFail();

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:display_sections,id',
        ]);

        foreach ($validated['order'] as $position => $sectionId) {
            DisplaySection::where('id', $sectionId)
                ->where('database_entity_id', $entity->id)
                ->update(['display_order' => ($position + 1) * 10]);
        }

        return redirect()
            ->route('backend.display.sections', $module)
            ->with('success', 'Section order updated successfully.');
    }
}
