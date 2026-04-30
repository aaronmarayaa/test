<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::orderBy('id')->get();

        return response()->json([
            'message' => 'Room list retrieved successfully.',
            'data' => $rooms
        ], 200);
    }

    public function show($id)
    {
        $room = Room::findOrFail($id);

        return response()->json([
            'message' => 'Room retrieved successfully.',
            'data' => $room
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'room_code' => 'required|string|max:50|unique:rooms,room_code',
            'room_name' => 'required|string|max:100',
            'room_type' => 'required|string|max:50',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:Active,Inactive',
        ]);

        $room = Room::create([
            'room_code' => $request->room_code,
            'room_name' => $request->room_name,
            'room_type' => $request->room_type,
            'capacity' => $request->capacity,
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'Room created successfully.',
            'data' => $room
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $room = Room::findOrFail($id);

        $request->validate([
            'room_code' => 'required|string|max:50|unique:rooms,room_code,' . $room->id,
            'room_name' => 'required|string|max:100',
            'room_type' => 'required|string|max:50',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:Active,Inactive',
        ]);

        $room->update([
            'room_code' => $request->room_code,
            'room_name' => $request->room_name,
            'room_type' => $request->room_type,
            'capacity' => $request->capacity,
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'Room updated successfully.',
            'data' => $room
        ], 200);
    }

    public function destroy($id)
    {
        $room = Room::findOrFail($id);
        $room->delete();

        return response()->json([
            'message' => 'Room deleted successfully.'
        ], 200);
    }
}