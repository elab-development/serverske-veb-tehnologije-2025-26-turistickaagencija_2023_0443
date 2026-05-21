<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Arrangement;
use Illuminate\Support\Facades\Validator;

class ArrangementController extends Controller
{
    public function index()
    {
        return response()->json(Arrangement::all());    
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'discount_percent' => 'nullable|integer|min:0|max:100',
            'is_last_minute' => 'boolean',
        ]);

        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

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

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'destination' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'duration_days' => 'sometimes|integer|min:1',
            'discount_percent' => 'sometimes|integer|min:0|max:100'
        ]);

        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
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
