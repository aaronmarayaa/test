<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    public function index(Request $request)
    {
        $query = Section::with('course:id,course_code,course_name');

        if ($request->has('archived')) {
            $archived = filter_var($request->archived, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if (!is_null($archived)) {
                $query->where('archived', $archived);
            }
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('year_level')) {
            $query->where('year_level', $request->year_level);
        }

        $sections = $query->orderBy('id')->get();

        return response()->json([
            'message' => 'Section list retrieved successfully.',
            'data' => $sections
        ], 200);
    }

    public function show($id)
    {
        $section = Section::with('course:id,course_code,course_name')->findOrFail($id);

        return response()->json([
            'message' => 'Section retrieved successfully.',
            'data' => $section
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'year_level' => 'required|integer|min:1|max:10',
            'section_name' => 'required|string|max:20',
            'section_code' => 'required|string|max:50|unique:sections,section_code',
            'capacity' => 'required|integer|min:1',
            'archived' => 'required|boolean',
        ]);

        $request->validate([
            'section_name' => [
                Rule::unique('sections')->where(function ($query) use ($validated) {
                    return $query->where('course_id', $validated['course_id'])
                        ->where('year_level', $validated['year_level'])
                        ->where('section_name', $validated['section_name']);
                }),
            ],
        ], [
            'section_name.unique' => 'This section name already exists for the selected course and year level.',
        ]);

        $section = Section::create($validated);

        $section->load('course:id,course_code,course_name');

        return response()->json([
            'message' => 'Section created successfully.',
            'data' => $section
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $section = Section::findOrFail($id);

        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'year_level' => 'required|integer|min:1|max:10',
            'section_name' => 'required|string|max:20',
            'section_code' => 'required|string|max:50|unique:sections,section_code,' . $section->id,
            'capacity' => 'required|integer|min:1',
            'archived' => 'required|boolean',
        ]);

        $request->validate([
            'section_name' => [
                Rule::unique('sections')->where(function ($query) use ($validated, $section) {
                    return $query->where('course_id', $validated['course_id'])
                        ->where('year_level', $validated['year_level'])
                        ->where('section_name', $validated['section_name'])
                        ->where('id', '!=', $section->id);
                }),
            ],
        ], [
            'section_name.unique' => 'This section name already exists for the selected course and year level.',
        ]);

        $section->update($validated);
        $section->load('course:id,course_code,course_name');

        return response()->json([
            'message' => 'Section updated successfully.',
            'data' => $section
        ], 200);
    }

    public function destroy($id)
    {
        $section = Section::findOrFail($id);
        $section->delete();

        return response()->json([
            'message' => 'Section deleted successfully.'
        ], 200);
    }

    public function archive($id)
    {
        $section = Section::findOrFail($id);
        $section->update(['archived' => true]);
        $section->load('course:id,course_code,course_name');

        return response()->json([
            'message' => 'Section archived successfully.',
            'data' => $section
        ], 200);
    }

    public function unarchive($id)
    {
        $section = Section::findOrFail($id);
        $section->update(['archived' => false]);
        $section->load('course:id,course_code,course_name');

        return response()->json([
            'message' => 'Section unarchived successfully.',
            'data' => $section
        ], 200);
    }
}