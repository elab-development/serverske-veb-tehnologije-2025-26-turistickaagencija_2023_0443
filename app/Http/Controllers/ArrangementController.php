<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Arrangement;

class ArrangementController extends Controller
{
    public function index()
    {
        return response()->json(Arrangement::all());    
    }

    public function store(Request $request)
    {
        $arrangement = Arrangement::create([
            'title' => $request->title,
            'destination' => $request->destination,
            'price' => $request->price,
            'duration_days' => $request->duration_days,
            'description' => $request->description,
            'discount_percent' => $request->discount_percent ?? 0,
            'is_last_minute' => $request->is_last_minute ?? false,
        ]);
        return response()->json($arrangement);
    }

    public function show($id){
        return Arrangement::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $arrangement = Arrangement::find($id);

        if(!$arrangement){
            return response()->json([
                'message' => 'Arrangement not found'
            ], 404);
        }

        $arrangement->update($request->all());
        return response()->json($arrangement);
    }

    public function destroy($id)
    {
        $arrangement = Arrangement::find($id);

        if(!$arrangement){
            return response()->json([
                'message' => 'Arrangement not found'
            ], 404);
        }

        $arrangement->delete();
        return response()->json([
            'message' => 'Arrangement deleted successfully'
        ]);

    }

}
