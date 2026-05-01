<?php

namespace App\Http\Controllers;

use App\Models\FacultySubject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FacultySubjectController extends Controller
{
    public function index(Request $request)
    {
        $query = FacultySubject::with([
            'instructor:id,instructor_name,employment_type,status,archived',
            'subject:id,subject_code,subject_name',
            'course:id,course_code,course_name,department_name',
        ]);

        if ($request->filled('instructor_id')) {
            $query->where('instructor_id', $request->instructor_id);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('session_type')) {
            $query->where('session_type', $request->session_type);
        }

        if ($request->has('is_primary')) {
            $isPrimary = filter_var($request->is_primary, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if (!is_null($isPrimary)) {
                $query->where('is_primary', $isPrimary);
            }
        }

        $facultySubjects = $query
            ->orderByDesc('is_primary')
            ->orderByDesc('priority_score')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'message' => 'Faculty subject list retrieved successfully.',
            'data' => $facultySubjects,
        ], 200);
    }

    public function show($id)
    {
        $facultySubject = FacultySubject::with([
            'instructor:id,instructor_name,employment_type,status,archived',
            'subject:id,subject_code,subject_name',
            'course:id,course_code,course_name,department_name',
        ])->findOrFail($id);

        return response()->json([
            'message' => 'Faculty subject retrieved successfully.',
            'data' => $facultySubject,
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'instructor_id' => 'required|exists:instructors,id',
            'subject_id' => 'required|exists:subjects,id',
            'course_id' => 'required|exists:courses,id',
            'session_type' => ['required', Rule::in(['lecture', 'laboratory', 'both'])],
            'priority_score' => 'required|integer|min:0|max:100',
            'is_primary' => 'required|boolean',
        ]);

        $request->validate([
            'subject_id' => [
                Rule::unique('faculty_subjects')->where(function ($query) use ($validated) {
                    return $query->where('instructor_id', $validated['instructor_id'])
                        ->where('subject_id', $validated['subject_id'])
                        ->where('course_id', $validated['course_id'])
                        ->where('session_type', $validated['session_type']);
                }),
            ],
        ], [
            'subject_id.unique' => 'This instructor is already assigned to this subject, course, and session type.',
        ]);

        // Primary means primary teacher for this subject + course + session type,
        // not primary teacher for all subjects of the instructor.
        if ((bool) $validated['is_primary']) {
            FacultySubject::where('subject_id', $validated['subject_id'])
                ->where('course_id', $validated['course_id'])
                ->where('session_type', $validated['session_type'])
                ->update(['is_primary' => false]);
        }

        $facultySubject = FacultySubject::create($validated);

        $facultySubject->load([
            'instructor:id,instructor_name,employment_type,status,archived',
            'subject:id,subject_code,subject_name',
            'course:id,course_code,course_name,department_name',
        ]);

        return response()->json([
            'message' => 'Faculty subject created successfully.',
            'data' => $facultySubject,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $facultySubject = FacultySubject::findOrFail($id);

        $validated = $request->validate([
            'instructor_id' => 'required|exists:instructors,id',
            'subject_id' => 'required|exists:subjects,id',
            'course_id' => 'required|exists:courses,id',
            'session_type' => ['required', Rule::in(['lecture', 'laboratory', 'both'])],
            'priority_score' => 'required|integer|min:0|max:100',
            'is_primary' => 'required|boolean',
        ]);

        $request->validate([
            'subject_id' => [
                Rule::unique('faculty_subjects')->where(function ($query) use ($validated, $facultySubject) {
                    return $query->where('instructor_id', $validated['instructor_id'])
                        ->where('subject_id', $validated['subject_id'])
                        ->where('course_id', $validated['course_id'])
                        ->where('session_type', $validated['session_type'])
                        ->where('id', '!=', $facultySubject->id);
                }),
            ],
        ], [
            'subject_id.unique' => 'This instructor is already assigned to this subject, course, and session type.',
        ]);

        if ((bool) $validated['is_primary']) {
            FacultySubject::where('subject_id', $validated['subject_id'])
                ->where('course_id', $validated['course_id'])
                ->where('session_type', $validated['session_type'])
                ->where('id', '!=', $facultySubject->id)
                ->update(['is_primary' => false]);
        }

        $facultySubject->update($validated);

        $facultySubject->load([
            'instructor:id,instructor_name,employment_type,status,archived',
            'subject:id,subject_code,subject_name',
            'course:id,course_code,course_name,department_name',
        ]);

        return response()->json([
            'message' => 'Faculty subject updated successfully.',
            'data' => $facultySubject,
        ], 200);
    }

    public function destroy($id)
    {
        $facultySubject = FacultySubject::findOrFail($id);
        $facultySubject->delete();

        return response()->json([
            'message' => 'Faculty subject deleted successfully.',
        ], 200);
    }
}
