<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::orderBy('room_type')
            ->orderBy('room_category')
            ->orderBy('room_floor_group')
            ->orderBy('room_priority')
            ->orderBy('room_code')
            ->get();

        return response()->json([
            'message' => 'Room list retrieved successfully.',
            'data' => $rooms,
        ], 200);
    }

    public function show($id)
    {
        $room = Room::findOrFail($id);

        return response()->json([
            'message' => 'Room retrieved successfully.',
            'data' => $room,
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_code' => 'required|string|max:50|unique:rooms,room_code',
            'room_name' => 'required|string|max:100',
            'room_type' => 'required|string|max:50',
            'room_category' => 'nullable|string|max:80',
            'room_floor_group' => 'nullable|integer|in:100,200',
            'room_priority' => 'nullable|integer|min:1|max:999',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:Active,Inactive,active,inactive',
        ]);

        $room = Room::create([
            'room_code' => $validated['room_code'],
            'room_name' => $validated['room_name'],
            'room_type' => $validated['room_type'],
            'room_category' => $validated['room_category'] ?? null,
            'room_floor_group' => $validated['room_floor_group'] ?? null,
            'room_priority' => $validated['room_priority'] ?? 100,
            'capacity' => $validated['capacity'],
            'status' => ucfirst(strtolower($validated['status'])),
        ]);

        return response()->json([
            'message' => 'Room created successfully.',
            'data' => $room,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $room = Room::findOrFail($id);

        $validated = $request->validate([
            'room_code' => 'required|string|max:50|unique:rooms,room_code,' . $room->id,
            'room_name' => 'required|string|max:100',
            'room_type' => 'required|string|max:50',
            'room_category' => 'nullable|string|max:80',
            'room_floor_group' => 'nullable|integer|in:100,200',
            'room_priority' => 'nullable|integer|min:1|max:999',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:Active,Inactive,active,inactive',
        ]);

        $room->update([
            'room_code' => $validated['room_code'],
            'room_name' => $validated['room_name'],
            'room_type' => $validated['room_type'],
            'room_category' => $validated['room_category'] ?? null,
            'room_floor_group' => $validated['room_floor_group'] ?? null,
            'room_priority' => $validated['room_priority'] ?? 100,
            'capacity' => $validated['capacity'],
            'status' => ucfirst(strtolower($validated['status'])),
        ]);

        return response()->json([
            'message' => 'Room updated successfully.',
            'data' => $room,
        ], 200);
    }

    public function destroy($id)
    {
        $room = Room::findOrFail($id);
        $room->delete();

        return response()->json([
            'message' => 'Room deleted successfully.',
        ], 200);
    }
}
