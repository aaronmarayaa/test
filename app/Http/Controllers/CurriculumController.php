<?php

namespace App\Http\Controllers;

use App\Models\Curriculum;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CurriculumController extends Controller
{
    public function index(Request $request)
    {
        $query = Curriculum::with([
            'course:id,course_code,course_name',
            'semester:id,semester_name,semester_order',
            'subject:id,subject_code,subject_name'
        ]);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->semester_id);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('year_level')) {
            $query->where('year_level', $request->year_level);
        }

        if ($request->has('active')) {
            $active = filter_var($request->active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if (!is_null($active)) {
                $query->where('active', $active);
            }
        }

        $curricula = $query
            ->orderBy('course_id')
            ->orderBy('year_level')
            ->orderBy('semester_id')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'message' => 'Curriculum list retrieved successfully.',
            'data' => $curricula
        ], 200);
    }

    public function show($id)
    {
        $curriculum = Curriculum::with([
            'course:id,course_code,course_name',
            'semester:id,semester_name,semester_order',
            'subject:id,subject_code,subject_name'
        ])->findOrFail($id);

        return response()->json([
            'message' => 'Curriculum retrieved successfully.',
            'data' => $curriculum
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'semester_id' => 'required|exists:semesters,id',
            'subject_id' => 'required|exists:subjects,id',
            'year_level' => 'required|integer|min:1|max:10',
            'sort_order' => 'required|integer|min:1',
            'active' => 'required|boolean',
        ]);

        $request->validate([
            'subject_id' => [
                Rule::unique('curricula')->where(function ($query) use ($validated) {
                    return $query->where('course_id', $validated['course_id'])
                        ->where('semester_id', $validated['semester_id'])
                        ->where('year_level', $validated['year_level'])
                        ->where('subject_id', $validated['subject_id']);
                }),
            ],
            'sort_order' => [
                Rule::unique('curricula')->where(function ($query) use ($validated) {
                    return $query->where('course_id', $validated['course_id'])
                        ->where('semester_id', $validated['semester_id'])
                        ->where('year_level', $validated['year_level'])
                        ->where('sort_order', $validated['sort_order']);
                }),
            ],
        ], [
            'subject_id.unique' => 'This subject already exists in the selected curriculum term.',
            'sort_order.unique' => 'This sort order is already used in the selected curriculum term.',
        ]);

        $curriculum = Curriculum::create($validated);

        $curriculum->load([
            'course:id,course_code,course_name',
            'semester:id,semester_name,semester_order',
            'subject:id,subject_code,subject_name'
        ]);

        return response()->json([
            'message' => 'Curriculum created successfully.',
            'data' => $curriculum
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $curriculum = Curriculum::findOrFail($id);

        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'semester_id' => 'required|exists:semesters,id',
            'subject_id' => 'required|exists:subjects,id',
            'year_level' => 'required|integer|min:1|max:10',
            'sort_order' => 'required|integer|min:1',
            'active' => 'required|boolean',
        ]);

        $request->validate([
            'subject_id' => [
                Rule::unique('curricula')->where(function ($query) use ($validated, $curriculum) {
                    return $query->where('course_id', $validated['course_id'])
                        ->where('semester_id', $validated['semester_id'])
                        ->where('year_level', $validated['year_level'])
                        ->where('subject_id', $validated['subject_id'])
                        ->where('id', '!=', $curriculum->id);
                }),
            ],
            'sort_order' => [
                Rule::unique('curricula')->where(function ($query) use ($validated, $curriculum) {
                    return $query->where('course_id', $validated['course_id'])
                        ->where('semester_id', $validated['semester_id'])
                        ->where('year_level', $validated['year_level'])
                        ->where('sort_order', $validated['sort_order'])
                        ->where('id', '!=', $curriculum->id);
                }),
            ],
        ], [
            'subject_id.unique' => 'This subject already exists in the selected curriculum term.',
            'sort_order.unique' => 'This sort order is already used in the selected curriculum term.',
        ]);

        $curriculum->update($validated);

        $curriculum->load([
            'course:id,course_code,course_name',
            'semester:id,semester_name,semester_order',
            'subject:id,subject_code,subject_name'
        ]);

        return response()->json([
            'message' => 'Curriculum updated successfully.',
            'data' => $curriculum
        ], 200);
    }

    public function destroy($id)
    {
        $curriculum = Curriculum::findOrFail($id);
        $curriculum->delete();

        return response()->json([
            'message' => 'Curriculum deleted successfully.'
        ], 200);
    }

    public function activate($id)
    {
        $curriculum = Curriculum::findOrFail($id);
        $curriculum->update(['active' => true]);

        $curriculum->load([
            'course:id,course_code,course_name',
            'semester:id,semester_name,semester_order',
            'subject:id,subject_code,subject_name'
        ]);

        return response()->json([
            'message' => 'Curriculum activated successfully.',
            'data' => $curriculum
        ], 200);
    }

    public function deactivate($id)
    {
        $curriculum = Curriculum::findOrFail($id);
        $curriculum->update(['active' => false]);

        $curriculum->load([
            'course:id,course_code,course_name',
            'semester:id,semester_name,semester_order',
            'subject:id,subject_code,subject_name'
        ]);

        return response()->json([
            'message' => 'Curriculum deactivated successfully.',
            'data' => $curriculum
        ], 200);
    }
}