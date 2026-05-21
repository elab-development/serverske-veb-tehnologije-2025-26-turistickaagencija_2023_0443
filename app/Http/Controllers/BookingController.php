<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;

class BookingController extends Controller
{
    public function index(){
        return response()->json(Booking::all());
    }

    public function store(Request $request){
        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'arrangement_id' => $request->arrangement_id,
            'number_of_people' => $request->number_of_people,
            'total_price' => $request->total_price,
            'travel_date' => $request->travel_date,
            'status' => 'pending'
        ]);

        return response()->json($booking);
    }

    public function show($id){
        return Booking::findOrFail($id);
    }

    public function update(Request $request, $id){
        $booking = Booking::find($id);

        if(!$booking){
            return response()->json([
                'message' => 'Booking not found'
            ], 404);
        }

        $booking->update($request->all());
        return response()->json($booking);
    }

    public function destroy($id){
        $booking = Booking::find($id);

        if(!$booking){
            return response()->json([
                'message' => 'Booking not found'
            ], 404);
        }

        $booking->delete();
        return response()->json([
            'message' => 'Booking deleted successfully'
        ]);
    }

}
