<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::orderBy('id')->get();

        return response()->json([
            'message' => 'Course list retrieved successfully.',
            'data' => $courses
        ], 200);
    }

    public function show($id)
    {
        $course = Course::findOrFail($id);

        return response()->json([
            'message' => 'Course retrieved successfully.',
            'data' => $course
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_code' => 'required|string|max:50|unique:courses,course_code',
            'course_name' => 'required|string|max:255',
            'department_name' => 'required|string|max:255',
            'archived' => 'required|boolean',
        ]);

        $course = Course::create([
            'course_code' => $request->course_code,
            'course_name' => $request->course_name,
            'department_name' => $request->department_name,
            'archived' => $request->archived,
        ]);

        return response()->json([
            'message' => 'Course created successfully.',
            'data' => $course
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        $request->validate([
            'course_code' => 'required|string|max:50|unique:courses,course_code,' . $course->id,
            'course_name' => 'required|string|max:255',
            'department_name' => 'required|string|max:255',
            'archived' => 'required|boolean',
        ]);

        $course->update([
            'course_code' => $request->course_code,
            'course_name' => $request->course_name,
            'department_name' => $request->department_name,
            'archived' => $request->archived,
        ]);

        return response()->json([
            'message' => 'Course updated successfully.',
            'data' => $course
        ], 200);
    }

    public function destroy($id)
    {
        $course = Course::findOrFail($id);
        $course->delete();

        return response()->json([
            'message' => 'Course deleted successfully.'
        ], 200);
    }
}