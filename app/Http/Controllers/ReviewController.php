<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ReviewResource;
use OpenApi\Attributes as OA;

class ReviewController extends Controller
{
    #[OA\Get(
        path: "/api/reviews",
        summary: "Lista svih recenzija",
        description: "Vraca paginiranu listu recenzija sa podacima o korisniku i aranzmanu.",
        tags: ["Recenzije"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Uspesno vracena lista recenzija"),
            new OA\Response(response: 401, description: "Neautorizovan pristup")
        ]
    )]
    public function index()
    {
        $paged = Review::with(['user','arrangement'])->paginate(5);

        return response()->json([
            'current_page'=> $paged->currentPage(),
            'data' => ReviewResource::collection($paged->items()),
            'total_pages' => $paged->lastPage(),
        ]);
    }

    #[OA\Post(
        path: "/api/reviews",
        summary: "Kreiranje recenzije",
        description: "Kreira novu recenziju za aranzman. Korisnik moze ostaviti samo jednu recenziju po aranzmanu.",
        tags: ["Recenzije"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["arrangement_id", "rating"],
                properties: [
                    new OA\Property(property: "arrangement_id", type: "integer", example: 1),
                    new OA\Property(property: "rating", type: "integer", minimum: 1, maximum: 5, example: 4),
                    new OA\Property(property: "comment", type: "string", example: "Odlicno putovanje!")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Recenzija uspesno kreirana"),
            new OA\Response(response: 409, description: "Vec ste ostavili recenziju za ovaj aranzman"),
            new OA\Response(response: 422, description: "Greska validacije")
        ]
    )]
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

        $existingReview = Review::where('user_id', $request->user()->id)
            ->where('arrangement_id', $request->arrangement_id)
            ->first();

        if($existingReview){
            return response()->json([
                'message' => 'You already reviewed this arrangement'
            ], 409);
        }

        $review = Review::create([
            'user_id' => $request->user()->id,
            'arrangement_id' => $request->arrangement_id,
            'rating' => $request->rating,
            'comment' => $request->comment
        ]);

        return response()->json($review);
    }

    #[OA\Get(
        path: "/api/reviews/{id}",
        summary: "Prikaz jedne recenzije",
        description: "Vraca detalje recenzije. Korisnik moze videti samo svoje recenzije.",
        tags: ["Recenzije"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: "Uspesno vracena recenzija"),
            new OA\Response(response: 403, description: "Nemate pristup ovoj recenziji"),
            new OA\Response(response: 404, description: "Recenzija nije pronadjena")
        ]
    )]
    public function show($id)
    {
        $review = Review::find($id);
        
        if(!$review){
            return response()->json([
                'message' => 'Review not found'
            ],404);
        }

        if($review->user_id !== request()->user()->id){
            return response()->json([
                'message' => 'Unauthorized'
            ],403);
        }

        return Review::with(['user','arrangement'])->findOrFail($id);
    }

    #[OA\Put(
        path: "/api/reviews/{id}",
        summary: "Izmena recenzije",
        description: "Menja podatke recenzije. Korisnik moze menjati samo svoje recenzije.",
        tags: ["Recenzije"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "rating", type: "integer", minimum: 1, maximum: 5, example: 5),
                    new OA\Property(property: "comment", type: "string", example: "Izmenjen komentar")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Recenzija uspesno izmenjena"),
            new OA\Response(response: 403, description: "Nemate pristup ovoj recenziji"),
            new OA\Response(response: 404, description: "Recenzija nije pronadjena"),
            new OA\Response(response: 422, description: "Greska validacije")
        ]
    )]
    public function update(Request $request, $id)
    {
        $review = Review::find($id);
        
        if(!$review){
            return response()->json([
                'message' => 'Review not found'
            ],404);
        }
        
        if($review->user_id !== request()->user()->id){
            return response()->json([
                'message' => 'Unauthorized'
            ],403);
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

        $review->update([
            'rating' => $request->rating ?? $review->rating,
            'comment' => $request->comment ?? $review->comment,
        ]);
        return response()->json($review);
    }

    #[OA\Delete(
        path: "/api/reviews/{id}",
        summary: "Brisanje recenzije",
        description: "Brise recenziju. Korisnik moze brisati samo svoje recenzije.",
        tags: ["Recenzije"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: "Recenzija uspesno obrisana"),
            new OA\Response(response: 403, description: "Nemate pristup ovoj recenziji"),
            new OA\Response(response: 404, description: "Recenzija nije pronadjena")
        ]
    )]
    public function destroy($id)
    {
        $review = Review::find($id);

        if(!$review){
            return response()->json([
                'message' => 'Review not found'
            ],404);
        }

        if($review->user_id !== request()->user()->id){
            return response()->json([
                'message' => 'Unauthorized'
            ],403);
        }

        $review->delete();
        return response()->json([
            'message' => 'Review deleted successfully'
        ]);
    }

    #[OA\Get(
        path: "/api/arrangements/{id}/reviews",
        summary: "Recenzije za aranzman",
        description: "Vraca sve recenzije za odredjeni aranzman.",
        tags: ["Recenzije"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: "Uspesno vracene recenzije"),
            new OA\Response(response: 401, description: "Neautorizovan pristup")
        ]
    )]
    public function byArrangement($id)
    {
        $reviews = Review::where('arrangement_id', $id)->get();
        return response()->json($reviews);
    }
}
