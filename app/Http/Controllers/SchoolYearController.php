<?php

namespace App\Http\Controllers;

use App\Models\SchoolYear;
use Illuminate\Http\Request;

class SchoolYearController extends Controller
{
    public function index()
    {
        return response()->json(SchoolYear::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'school_year' => 'required|unique:school_years',
            'status' => 'required|in:Active,Inactive'
        ]);

        if ($request->status === 'Active') {
            SchoolYear::where('status', 'Active')->update(['status' => 'Inactive']);
        }

        $schoolYear = SchoolYear::create($request->all());

        return response()->json([
            'message' => 'Created successfully',
            'data' => $schoolYear
        ]);
    }

    public function edit($id)
    {
        $schoolYear = SchoolYear::findOrFail($id);

        return response()->json([
            'data' => $schoolYear
        ]);
    }

    public function update(Request $request, $id)
    {
        $schoolYear = SchoolYear::findOrFail($id);

        if ($request->status === 'Active') {
            SchoolYear::where('status', 'Active')
                ->where('id', '!=', $id)
                ->update(['status' => 'Inactive']);
        }

        $schoolYear->update($request->all());

        return response()->json([
            'message' => 'Updated successfully',
            'data' => $schoolYear
        ]);
    }

    public function destroy($id)
    {
        SchoolYear::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }
}