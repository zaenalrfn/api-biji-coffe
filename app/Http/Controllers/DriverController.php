<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    /**
     * Get list of active drivers.
     */
    public function index()
    {
        $drivers = Driver::where('is_active', true)->get();
        return response()->json([
            'status' => 'success',
            'data' => $drivers
        ]);
    }

    /**
     * Get driver location.
     */
    public function getLocation($id)
    {
        $driver = Driver::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $driver->id,
                'current_lat' => $driver->current_lat,
                'current_lng' => $driver->current_lng,
                'last_updated' => $driver->updated_at,
            ]
        ], 200);
    }

    /**
     * Update driver location (optional helper for testing/driver app).
     */
    public function updateLocation(Request $request, $id)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $driver = Driver::findOrFail($id);
        $driver->update([
            'current_lat' => $request->lat,
            'current_lng' => $request->lng,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Location updated successfully'
        ]);
    }
}
