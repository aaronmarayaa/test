<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use Illuminate\Http\Request;

class InstructorController extends Controller
{
    public function index(Request $request)
    {
        $query = Instructor::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('archived')) {
            $archived = filter_var($request->archived, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if (!is_null($archived)) {
                $query->where('archived', $archived);
            }
        }

        $instructors = $query->orderByDesc('id')->get();

        return response()->json([
            'message' => 'Instructor list retrieved successfully.',
            'data' => $instructors
        ], 200);
    }

    public function show($id)
    {
        $instructor = Instructor::findOrFail($id);

        return response()->json([
            'message' => 'Instructor retrieved successfully.',
            'data' => $instructor
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_no' => 'required|string|max:50|unique:instructors,employee_no',
            'instructor_name' => 'required|string|max:150',
            'employment_type' => 'required|in:full_time,part_time',
            'status' => 'required|in:active,inactive',
            'archived' => 'required|boolean',
        ]);

        $instructor = Instructor::create([
            'employee_no' => $request->employee_no,
            'instructor_name' => $request->instructor_name,
            'employment_type' => $request->employment_type,
            'status' => $request->status,
            'archived' => $request->archived,
        ]);

        return response()->json([
            'message' => 'Instructor created successfully.',
            'data' => $instructor
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $instructor = Instructor::findOrFail($id);

        $request->validate([
            'employee_no' => 'required|string|max:50|unique:instructors,employee_no,' . $instructor->id,
            'instructor_name' => 'required|string|max:150',
            'employment_type' => 'required|in:full_time,part_time',
            'status' => 'required|in:active,inactive',
            'archived' => 'required|boolean',
        ]);

        $instructor->update([
            'employee_no' => $request->employee_no,
            'instructor_name' => $request->instructor_name,
            'employment_type' => $request->employment_type,
            'status' => $request->status,
            'archived' => $request->archived,
        ]);

        return response()->json([
            'message' => 'Instructor updated successfully.',
            'data' => $instructor
        ], 200);
    }

    public function destroy($id)
    {
        $instructor = Instructor::findOrFail($id);
        $instructor->delete();

        return response()->json([
            'message' => 'Instructor deleted successfully.'
        ], 200);
    }

    public function archive($id)
    {
        $instructor = Instructor::findOrFail($id);
        $instructor->update(['archived' => true]);

        return response()->json([
            'message' => 'Instructor archived successfully.',
            'data' => $instructor
        ], 200);
    }

    public function unarchive($id)
    {
        $instructor = Instructor::findOrFail($id);
        $instructor->update(['archived' => false]);

        return response()->json([
            'message' => 'Instructor unarchived successfully.',
            'data' => $instructor
        ], 200);
    }
}