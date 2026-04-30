<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Subject::query();

        if ($request->has('archived')) {
            $archived = filter_var($request->archived, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if (!is_null($archived)) {
                $query->where('archived', $archived);
            }
        }

        if ($request->filled('subject_category')) {
            $query->where('subject_category', $request->subject_category);
        }

        if ($request->filled('room_type_required')) {
            $query->where('room_type_required', $request->room_type_required);
        }

        $subjects = $query->orderBy('id')->get();

        return response()->json([
            'message' => 'Subject list retrieved successfully.',
            'data' => $subjects
        ], 200);
    }

    public function show($id)
    {
        $subject = Subject::findOrFail($id);

        return response()->json([
            'message' => 'Subject retrieved successfully.',
            'data' => $subject
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_code' => ['required', 'string', 'max:50', Rule::unique('subjects')->where(fn ($query) => $query->where('subject_name', $request->subject_name))],
            'subject_name' => 'required|string|max:255',
            'units' => 'required|numeric|min:0|max:99.9',
            'total_hours_per_week' => 'required|numeric|min:0|max:99.99',
            'lecture_hours' => 'required|numeric|min:0|max:99.99',
            'laboratory_hours' => 'required|numeric|min:0|max:99.99',
            'allow_split_sessions' => 'required|boolean',
            'break_minutes_per_week' => 'required|integer|min:0',
            'preferred_session_count' => 'required|integer|min:1',
            'max_hours_per_day' => 'required|numeric|min:0|max:99.99',
            'room_type_required' => 'required|string|max:50',
            'lecture_room_type_required' => 'nullable|string|max:50',
            'laboratory_room_type_required' => 'nullable|string|max:50',
            'subject_category' => 'required|string|max:50',
            'archived' => 'required|boolean',
        ]);

        $subject = Subject::create($validated);

        return response()->json([
            'message' => 'Subject created successfully.',
            'data' => $subject
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        $validated = $request->validate([
            'subject_code' => ['required', 'string', 'max:50', Rule::unique('subjects')->where(fn ($query) => $query->where('subject_name', $request->subject_name))->ignore($subject->id)],
            'subject_name' => 'required|string|max:255',
            'units' => 'required|numeric|min:0|max:99.9',
            'total_hours_per_week' => 'required|numeric|min:0|max:99.99',
            'lecture_hours' => 'required|numeric|min:0|max:99.99',
            'laboratory_hours' => 'required|numeric|min:0|max:99.99',
            'allow_split_sessions' => 'required|boolean',
            'break_minutes_per_week' => 'required|integer|min:0',
            'preferred_session_count' => 'required|integer|min:1',
            'max_hours_per_day' => 'required|numeric|min:0|max:99.99',
            'room_type_required' => 'required|string|max:50',
            'lecture_room_type_required' => 'nullable|string|max:50',
            'laboratory_room_type_required' => 'nullable|string|max:50',
            'subject_category' => 'required|string|max:50',
            'archived' => 'required|boolean',
        ]);

        $subject->update($validated);

        return response()->json([
            'message' => 'Subject updated successfully.',
            'data' => $subject
        ], 200);
    }

    public function destroy($id)
    {
        $subject = Subject::findOrFail($id);
        $subject->delete();

        return response()->json([
            'message' => 'Subject deleted successfully.'
        ], 200);
    }

    public function archive($id)
    {
        $subject = Subject::findOrFail($id);
        $subject->update(['archived' => true]);

        return response()->json([
            'message' => 'Subject archived successfully.',
            'data' => $subject
        ], 200);
    }

    public function unarchive($id)
    {
        $subject = Subject::findOrFail($id);
        $subject->update(['archived' => false]);

        return response()->json([
            'message' => 'Subject unarchived successfully.',
            'data' => $subject
        ], 200);
    }
}