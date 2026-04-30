<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use Illuminate\Http\Request;

class SemesterController extends Controller
{
    public function index()
    {
        $semesters = Semester::orderBy('semester_order')->get();

        return response()->json([
            'message' => 'Semester list retrieved successfully.',
            'data' => $semesters
        ], 200);
    }

    public function show($id)
    {
        $semester = Semester::findOrFail($id);

        return response()->json([
            'message' => 'Semester retrieved successfully.',
            'data' => $semester
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'semester_name' => 'required|string|max:100|unique:semesters,semester_name',
            'semester_order' => 'required|integer|unique:semesters,semester_order',
            'status' => 'required|in:Active,Inactive',
        ]);

        $semester = Semester::create([
            'semester_name' => $request->semester_name,
            'semester_order' => $request->semester_order,
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'Semester created successfully.',
            'data' => $semester
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $semester = Semester::findOrFail($id);

        $request->validate([
            'semester_name' => 'required|string|max:100|unique:semesters,semester_name,' . $semester->id,
            'semester_order' => 'required|integer|unique:semesters,semester_order,' . $semester->id,
            'status' => 'required|in:Active,Inactive',
        ]);

        $semester->update([
            'semester_name' => $request->semester_name,
            'semester_order' => $request->semester_order,
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'Semester updated successfully.',
            'data' => $semester
        ], 200);
    }

    public function destroy($id)
    {
        $semester = Semester::findOrFail($id);
        $semester->delete();

        return response()->json([
            'message' => 'Semester deleted successfully.'
        ], 200);
    }
}