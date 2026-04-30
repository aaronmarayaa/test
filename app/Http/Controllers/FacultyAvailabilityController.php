<?php

namespace App\Http\Controllers;

use App\Models\FacultyAvailability;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FacultyAvailabilityController extends Controller
{
    public function index(Request $request)
    {
        $query = FacultyAvailability::with([
            'instructor:id,instructor_name,employment_type',
            'schoolYear:id,school_year',
            'semester:id,semester_name,semester_order',
        ]);

        if ($request->filled('instructor_id')) {
            $query->where('instructor_id', $request->instructor_id);
        }

        if ($request->filled('school_year_id')) {
            $query->where('school_year_id', $request->school_year_id);
        }

        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->semester_id);
        }

        if ($request->filled('day')) {
            $query->where('day', $request->day);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $data = $query
            ->orderBy('instructor_id')
            ->orderByRaw("
                FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
            ")
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'message' => 'Faculty availability list retrieved successfully.',
            'data' => $data
        ], 200);
    }

    public function show($id)
    {
        $item = FacultyAvailability::with([
            'instructor:id,instructor_name,employment_type',
            'schoolYear:id,school_year',
            'semester:id,semester_name,semester_order',
        ])->findOrFail($id);

        return response()->json([
            'message' => 'Faculty availability retrieved successfully.',
            'data' => $item
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'instructor_id' => 'required|exists:instructors,id',
            'school_year_id' => 'required|exists:school_years,id',
            'semester_id' => 'required|exists:semesters,id',
            'day' => ['required', Rule::in([
                'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'
            ])],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'status' => 'required|in:Available,Unavailable',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validated['start_time'] >= $validated['end_time']) {
            return response()->json([
                'message' => 'End time must be later than start time.'
            ], 422);
        }

        $exists = FacultyAvailability::where('instructor_id', $validated['instructor_id'])
            ->where('school_year_id', $validated['school_year_id'])
            ->where('semester_id', $validated['semester_id'])
            ->where('day', $validated['day'])
            ->where('start_time', $validated['start_time'])
            ->where('end_time', $validated['end_time'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'This availability slot already exists for the selected instructor, school year, and semester.'
            ], 422);
        }

        $item = FacultyAvailability::create($validated);

        $item->load([
            'instructor:id,instructor_name,employment_type',
            'schoolYear:id,school_year',
            'semester:id,semester_name,semester_order',
        ]);

        return response()->json([
            'message' => 'Faculty availability created successfully.',
            'data' => $item
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $item = FacultyAvailability::findOrFail($id);

        $validated = $request->validate([
            'instructor_id' => 'required|exists:instructors,id',
            'school_year_id' => 'required|exists:school_years,id',
            'semester_id' => 'required|exists:semesters,id',
            'day' => ['required', Rule::in([
                'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'
            ])],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'status' => 'required|in:Available,Unavailable',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validated['start_time'] >= $validated['end_time']) {
            return response()->json([
                'message' => 'End time must be later than start time.'
            ], 422);
        }

        $exists = FacultyAvailability::where('instructor_id', $validated['instructor_id'])
            ->where('school_year_id', $validated['school_year_id'])
            ->where('semester_id', $validated['semester_id'])
            ->where('day', $validated['day'])
            ->where('start_time', $validated['start_time'])
            ->where('end_time', $validated['end_time'])
            ->where('id', '!=', $item->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'This availability slot already exists for the selected instructor, school year, and semester.'
            ], 422);
        }

        $item->update($validated);

        $item->load([
            'instructor:id,instructor_name,employment_type',
            'schoolYear:id,school_year',
            'semester:id,semester_name,semester_order',
        ]);

        return response()->json([
            'message' => 'Faculty availability updated successfully.',
            'data' => $item
        ], 200);
    }

    public function destroy($id)
    {
        $item = FacultyAvailability::findOrFail($id);
        $item->delete();

        return response()->json([
            'message' => 'Faculty availability deleted successfully.'
        ], 200);
    }
}