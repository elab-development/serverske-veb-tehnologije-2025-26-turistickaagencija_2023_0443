<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\BookingResource;
use OpenApi\Attributes as OA;

class BookingController extends Controller
{
    #[OA\Get(
        path: "/api/bookings",
        summary: "Lista svih rezervacija",
        description: "Vraca paginiranu listu rezervacija sa podacima o korisniku i aranzmanu.",
        tags: ["Rezervacije"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Uspesno vracena lista rezervacija"),
            new OA\Response(response: 401, description: "Neautorizovan pristup")
        ]
    )]
    public function index(){
        $paged = Booking::with(['user','arrangement'])->paginate(5);

        return response()->json([
            'current_page'=> $paged->currentPage(),
            'data' => BookingResource::collection($paged->items()),
            'total_pages' => $paged->lastPage(),
        ]);
    }

    #[OA\Post(
        path: "/api/bookings",
        summary: "Kreiranje rezervacije",
        description: "Kreira novu rezervaciju za ulogovanog korisnika. Cena se automatski racuna na osnovu aranzmana i broja osoba.",
        tags: ["Rezervacije"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["arrangement_id", "number_of_people", "travel_date"],
                properties: [
                    new OA\Property(property: "arrangement_id", type: "integer", example: 1),
                    new OA\Property(property: "number_of_people", type: "integer", example: 2),
                    new OA\Property(property: "travel_date", type: "string", format: "date", example: "2026-08-15")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Rezervacija uspesno kreirana"),
            new OA\Response(response: 422, description: "Greska validacije"),
            new OA\Response(response: 500, description: "Greska pri kreiranju rezervacije")
        ]
    )]
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

        DB::beginTransaction();

        try{
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

            DB::commit();

            return response()->json($booking);
        }
        catch (\Exception $e){
            DB::rollBack();

            return response()->json([
                'message' => 'Booking failed',
                'error' => $e->getMessage()
            ],500);
        };
    }

    #[OA\Get(
        path: "/api/bookings/{id}",
        summary: "Prikaz jedne rezervacije",
        description: "Vraca detalje rezervacije. Korisnik moze videti samo svoje rezervacije.",
        tags: ["Rezervacije"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: "Uspesno vracena rezervacija"),
            new OA\Response(response: 403, description: "Nemate pristup ovoj rezervaciji"),
            new OA\Response(response: 404, description: "Rezervacija nije pronadjena")
        ]
    )]
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

    #[OA\Put(
        path: "/api/bookings/{id}",
        summary: "Izmena rezervacije",
        description: "Menja podatke rezervacije. Korisnik moze menjati samo svoje rezervacije.",
        tags: ["Rezervacije"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "arrangement_id", type: "integer", example: 2),
                    new OA\Property(property: "number_of_people", type: "integer", example: 3),
                    new OA\Property(property: "travel_date", type: "string", format: "date", example: "2026-09-01")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Rezervacija uspesno izmenjena"),
            new OA\Response(response: 403, description: "Nemate pristup ovoj rezervaciji"),
            new OA\Response(response: 404, description: "Rezervacija nije pronadjena"),
            new OA\Response(response: 422, description: "Greska validacije")
        ]
    )]
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

    #[OA\Delete(
        path: "/api/bookings/{id}",
        summary: "Brisanje rezervacije",
        description: "Brise rezervaciju. Korisnik moze brisati samo svoje rezervacije.",
        tags: ["Rezervacije"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: "Rezervacija uspesno obrisana"),
            new OA\Response(response: 403, description: "Nemate pristup ovoj rezervaciji"),
            new OA\Response(response: 404, description: "Rezervacija nije pronadjena")
        ]
    )]
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

    #[OA\Get(
        path: "/api/bookings/export/csv",
        summary: "Eksport rezervacija u CSV",
        description: "Preuzima sve rezervacije u CSV formatu. Dostupno samo administratorima.",
        tags: ["Rezervacije"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "CSV fajl uspesno generisan"),
            new OA\Response(response: 401, description: "Neautorizovan pristup")
        ]
    )]
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

    #[OA\Get(
        path: "/api/arrangements/{id}/bookings",
        summary: "Rezervacije za aranzman",
        description: "Vraca sve rezervacije za odredjeni aranzman. Dostupno administratorima i menadzeru.",
        tags: ["Rezervacije"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: "Uspesno vracene rezervacije"),
            new OA\Response(response: 401, description: "Neautorizovan pristup")
        ]
    )]
    public function byArrangement($id)
    {
        $bookings = Booking::where('arrangement_id', $id)->get();
        return response()->json($bookings);
    }
}
