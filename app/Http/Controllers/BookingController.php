<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\BookingResource;

class BookingController extends Controller
{
    public function index(){
        $paged = Booking::with(['user','arrangement'])->paginate(5);

        return response()->json([
            'current_page'=> $paged->currentPage(),
            'data' => BookingResource::collection($paged->items()),
            'total_pages' => $paged->lastPage(),
        ]);
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(),[
            'arrangement_id' => 'required|exists:arrangements,id',
            'number_of_people' => 'required|integer|min:1',
            'travel_date' => 'required|date|after_or_equal:today',
        ]);

        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        };

        $arrangement = \App\Models\Arrangement::findOrFail($request->arrangement_id);

        $totalPrice = $arrangement->price;

        if($arrangement->discount_percent > 0){
            $totalPrice -= $totalPrice * ($arrangement->discount_percent / 100);
        }

        $totalPrice *= $request->number_of_people;
        $totalPrice = ceil($totalPrice);

        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'arrangement_id' => $request->arrangement_id,
            'number_of_people' => $request->number_of_people,
            'total_price' => $totalPrice,
            'travel_date' => $request->travel_date
        ]);

        return response()->json($booking);
    }

    public function show($id){
         $booking = Booking::find($id);

        if(!$booking){
            return response()->json([
                'message' => 'Booking not found'
            ], 404);
        }

        // IDOR
        if($booking->user_id !== request()->user()->id){
            return response()->json([
                'message' => 'Unauthorized'
            ],403);
        }

        return Booking::with(['user','arrangement'])->findOrFail($id);
    }

    public function update(Request $request, $id){
        $booking = Booking::find($id);

        if(!$booking){
            return response()->json([
                'message' => 'Booking not found'
            ], 404);
        }

        if($booking->user_id !== request()->user()->id){
            return response()->json([
                'message' => 'Unauthorized'
            ],403);
        }

        $validator = Validator::make($request->all(),[
            'arrangement_id' => 'sometimes|exists:arrangements,id',
            'number_of_people' => 'sometimes|integer|min:1',
            'travel_date' => 'sometimes|date|after_or_equal:today',
        ]);

        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        };

        $arrangement = $booking->arrangement;

        $totalPrice = $arrangement->price;

        if($arrangement->discount_percent > 0){
            $totalPrice -= $totalPrice * ($arrangement->discount_percent / 100);
        }

        $totalPrice *= $request->number_of_people;
        $totalPrice = ceil($totalPrice);

        $booking->update([
            'arrangement_id' => $request->arrangement_id ?? $booking->arrangement_id,
            'number_of_people' => $request->number_of_people ?? $booking->number_of_people,
            'travel_date' => $request->travel_date ?? $booking->travel_date,
            'total_price' => $totalPrice
        ]);
        return response()->json($booking);
    }

    public function destroy($id){
        $booking = Booking::find($id);

        if(!$booking){
            return response()->json([
                'message' => 'Booking not found'
            ], 404);
        }

        if($booking->user_id !== request()->user()->id){
            return response()->json([
                'message' => 'Unauthorized'
            ],403);
        }
        
        $booking->delete();
        return response()->json([
            'message' => 'Booking deleted successfully'
        ]);
    }

    public function exportCsv(){
        $bookings = Booking::with(['user', 'arrangement'])->get();
        $filename = 'bookings.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename"
        ];

        $callback = function () use ($bookings){
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'ID',
                'User',
                'Arrangement',
                'People',
                'Total Price',
                'Travel Date'
            ]);

            foreach($bookings as $booking){
                fputcsv($file, [
                    $booking->id,
                    $booking->user->name ?? 'N/A',
                    $booking->arrangement->title ?? 'N/A',
                    $booking->number_of_people,
                    $booking->total_price,
                    $booking->travel_date,
                ]);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function byArrangement($id)
    {
        $bookings = Booking::where('arrangement_id', $id)->get();
        return response()->json($bookings);
    }
}
