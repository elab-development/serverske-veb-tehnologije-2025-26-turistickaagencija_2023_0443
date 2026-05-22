<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function index()
    {
        return response()->json(
            Review::with(['user','arrangement'])->get()
        );
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'arrangement_id' => 'required|exists:arrangements,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ],422);
        }

        $review = Review::create([
            'user_id' => $request->user()->id,
            'arrangement_id' => $request->arrangement_id,
            'rating' => $request->rating,
            'comment' => $request->comment
        ]);

        return response()->json($review);
    }

    public function show($id)
    {
        $review = Review::find($id);
        
        if(!$review){
            return response()->json([
                'message' => 'Review not found'
            ],404);
        }

        return Review::with(['user','arrangement'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $review = Review::find($id);
        
        if(!$review){
            return response()->json([
                'message' => 'Review not found'
            ],404);
        }

        $validator = Validator::make($request->all(),[
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'sometimes|string'
        ]);

        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ],422);
        }  

        $review->update($request->all());
        return response()->json($review);
    }

    public function destroy($id)
    {
        $review = Review::find($id);

        if(!$review){
            return response()->json([
                'message' => 'Review not found'
            ],404);
        }

        $review->delete();
        return response()->json([
            'message' => 'Review deleted successfully'
        ]);
    }
}
