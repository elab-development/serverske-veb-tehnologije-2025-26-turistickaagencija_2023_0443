<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Arrangement;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ArrangementResource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class ArrangementController extends Controller
{
    #[OA\Get(
        path: "/api/arrangements",
        summary: "Lista svih aranzmana",
        description: "Vraca paginiranu listu aranzmana sa opcionalnim filterima i sortiranjem.",
        tags: ["Aranzmani"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "destination", in: "query", required: false, schema: new OA\Schema(type: "string"), example: "Greece"),
            new OA\Parameter(name: "last_minute", in: "query", required: false, schema: new OA\Schema(type: "integer", enum: [0, 1]), example: 1),
            new OA\Parameter(name: "min_price", in: "query", required: false, schema: new OA\Schema(type: "number"), example: 100),
            new OA\Parameter(name: "max_price", in: "query", required: false, schema: new OA\Schema(type: "number"), example: 1000),
            new OA\Parameter(name: "sort", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["price_asc", "price_desc", "duration_asc", "duration_desc"]))
        ],
        responses: [
            new OA\Response(response: 200, description: "Uspesno vracena lista aranzmana"),
            new OA\Response(response: 401, description: "Neautorizovan pristup")
        ]
    )]
    public function index(Request $request) 
    {
        $cacheKey = 'arrangements_cache' . md5(json_encode($request->all()));

        $result = Cache::remember($cacheKey, 60, function () use ($request) {
            Log::info('CACHE MISS - DB QUERY EXECUTED');
        
            $query = Arrangement::query();

            if ($request->filled('last_minute')) {
                $query->where('is_last_minute', (int) $request->last_minute);
            }
            if ($request->filled('destination')){
                $query->where('destination', 'like', '%' . $request->destination . '%');
            }
            if($request->filled('min_price')){
                $query->where('price', '>=', $request->min_price);
            }
            if($request->filled('max_price')){
                $query->where('price', '<=', $request->max_price);
            }

            if($request->filled('sort')){
                if($request->sort == 'price_asc'){
                    $query->orderBy('price', 'asc');
                }
                if($request->sort == 'price_desc'){
                    $query->orderBy('price', 'desc');
                }
                if($request->sort == 'duration_asc'){
                    $query->orderBy('duration_days', 'asc');
                }
                if($request->sort == 'duration_desc'){
                    $query->orderBy('duration_days', 'desc');
                }
            }

            $paged = $query->paginate(5);
            return [
                'current_page' =>$paged->currentPage(),
                'data' => ArrangementResource::collection($paged->items()),
                'total_pages' => $paged->lastPage()
            ];
        });
        return response()->json($result);
    }

    #[OA\Post(
        path: "/api/arrangements",
        summary: "Kreiranje novog aranzmana",
        description: "Kreira novi turisticki aranzman. Dostupno samo administratorima i menadzeru.",
        tags: ["Aranzmani"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["title", "destination", "price", "duration_days"],
                properties: [
                    new OA\Property(property: "title", type: "string", example: "Letovanje u Grckoj"),
                    new OA\Property(property: "destination", type: "string", example: "Greece"),
                    new OA\Property(property: "price", type: "number", example: 499.99),
                    new OA\Property(property: "duration_days", type: "integer", example: 7),
                    new OA\Property(property: "description", type: "string", example: "Predivno letovanje"),
                    new OA\Property(property: "discount_percent", type: "integer", example: 10),
                    new OA\Property(property: "is_last_minute", type: "integer", enum: [0, 1], example: 0)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Aranzman uspesno kreiran"),
            new OA\Response(response: 422, description: "Greska validacije"),
            new OA\Response(response: 401, description: "Neautorizovan pristup")
        ]
    )]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'discount_percent' => 'nullable|integer|min:0|max:100',
            'is_last_minute' => 'nullable|in:0,1',
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
        Cache::forget('arrangements_cache');
        return response()->json($arrangement);
    }

    #[OA\Get(
        path: "/api/arrangements/{id}",
        summary: "Prikaz jednog aranzmana",
        description: "Vraca detalje aranzmana na osnovu ID-a.",
        tags: ["Aranzmani"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: "Uspesno vrácen aranzman"),
            new OA\Response(response: 404, description: "Aranzman nije pronadjen")
        ]
    )]
    public function show($id){
        $arrangement = Arrangement::find($id);
        if(!$arrangement){
            return response()->json([
                'message' => 'Arrangement not found'
            ], 404);
        }
        return Arrangement::findOrFail($id);
    }

    #[OA\Put(
        path: "/api/arrangements/{id}",
        summary: "Izmena aranzmana",
        description: "Menja podatke postojeceg aranzmana. Dostupno samo administratorima i menadzeru.",
        tags: ["Aranzmani"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "title", type: "string", example: "Novo ime aranzmana"),
                    new OA\Property(property: "price", type: "number", example: 599.99),
                    new OA\Property(property: "discount_percent", type: "integer", example: 15),
                    new OA\Property(property: "is_last_minute", type: "boolean", example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Aranzman uspesno izmenjen"),
            new OA\Response(response: 404, description: "Aranzman nije pronadjen"),
            new OA\Response(response: 422, description: "Greska validacije")
        ]
    )]
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
            'discount_percent' => 'sometimes|integer|min:0|max:100',
            'is_last_minute' => 'sometimes|boolean',
        ]);

        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $arrangement->update([
            'title' => $request->title ?? $arrangement->title,
            'destination' => $request->destination ?? $arrangement->destination,
            'price' => $request->price ?? $arrangement->price,
            'duration_days' => $request->duration_days ?? $arrangement->duration_days,
            'description' => $request->description ?? $arrangement->description,
            'discount_percent' => $request->discount_percent ?? $arrangement->discount_percent,
            'is_last_minute' => $request->is_last_minute ?? $arrangement->is_last_minute,
        ]);
        Cache::forget('arrangements_cache');
        return response()->json($arrangement);
    }

    #[OA\Delete(
        path: "/api/arrangements/{id}",
        summary: "Brisanje aranzmana",
        description: "Brise aranzman na osnovu ID-a. Dostupno samo administratorima.",
        tags: ["Aranzmani"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: "Aranzman uspesno obrisan"),
            new OA\Response(response: 404, description: "Aranzman nije pronadjen")
        ]
    )]
    public function destroy($id)
    {
        $arrangement = Arrangement::find($id);
        if(!$arrangement){
            return response()->json([
                'message' => 'Arrangement not found'
            ], 404);
        }
        $arrangement->delete();
        Cache::forget('arrangements_cache');
        return response()->json([
            'message' => 'Arrangement deleted successfully'
        ]);
    }

    #[OA\Get(
        path: "/api/arrangements/export/csv",
        summary: "Eksport aranzmana u CSV",
        description: "Preuzima sve aranzmane u CSV formatu.",
        tags: ["Aranzmani"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "CSV fajl uspesno generisan"),
            new OA\Response(response: 401, description: "Neautorizovan pristup")
        ]
    )]
    public function exportCsv(){
        $arrangements = Arrangement::all();
        $filename = 'arrangements.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
        ];
        $callback = function () use ($arrangements){
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID',
                'Title',
                'Destination',
                'Price',
                'Duration Days',
                'Discount Percent',
                'Last Minute'
            ]);

            foreach($arrangements as $arrangement){
                fputcsv($file, [
                    $arrangement->id,
                    $arrangement->title,
                    $arrangement->destination,
                    $arrangement->price,
                    $arrangement->duration_days,
                    $arrangement->discount_percent,
                    $arrangement->is_last_minute
                ]);
            }
            fclose($file);
        };
        return response()->stream($callback,200,$headers);
    }

    #[OA\Get(
        path: "/api/weather",
        summary: "Vremenski uslovi po destinaciji",
        description: "Vraca trenutne vremenske uslove za unetu destinaciju koristeci OpenWeatherMap API.",
        tags: ["Vreme"],
        parameters: [
            new OA\Parameter(name: "destination", in: "query", required: true, schema: new OA\Schema(type: "string"), example: "Belgrade")
        ],
        responses: [
            new OA\Response(response: 200, description: "Uspesno vraceni vremenski podaci"),
            new OA\Response(response: 422, description: "Greska validacije"),
            new OA\Response(response: 500, description: "Vremenski podaci nisu dostupni")
        ]
    )]
    public function weather(Request $request){
        $validator = Validator::make($request->all(), [
            'destination' => 'required|string|alpha'
        ]);

        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $destination = $request->destination;

        $response = Http::get('https://api.openweathermap.org/data/2.5/weather', [
            'q' => $destination,
            'appid' => env('WEATHER_API_KEY'),
            'units' => 'metric'
        ]);

        if($response->failed()){
            return response()->json([
                'message' => 'Weather data not available'
            ], 500);
        }

        $data = $response->json();

        return response()->json([
            'destination' => $destination,
            'temperature' => $data['main']['temp'],
            'feels_like' => $data['main']['feels_like'],
            'weather' => $data['weather'][0]['description'],
            'humidity' => $data['main']['humidity']
        ]);
    }

    #[OA\Get(
        path: "/api/arrangements/{id}/weather",
        summary: "Vremenski uslovi za destinaciju aranzmana",
        description: "Vraca trenutne vremenske uslove za destinaciju konkretnog aranzmana.",
        tags: ["Vreme"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: "Uspesno vraceni vremenski podaci"),
            new OA\Response(response: 404, description: "Aranzman nije pronadjen"),
            new OA\Response(response: 500, description: "Vremenski podaci nisu dostupni")
        ]
    )]
    public function weatherByArrangement($id){
        $arrangement = Arrangement::find($id);

        if(!$arrangement){
            return response()->json([
                'message' => 'Arrangement not found'
            ],404);
        }

        $destination = $arrangement->destination;

        $response = Http::get('https://api.openweathermap.org/data/2.5/weather', [
            'q' => $destination,
            'appid' => env('WEATHER_API_KEY'),
            'units' => 'metric'
        ]);

        if($response->failed()){
            return response()->json([
                'message' => 'Weather data not available'
            ], 500);
        }

        $data = $response->json();

        return response()->json([
            'arrangement' => [
                'id' => $arrangement->id,
                'title' => $arrangement->title,
                'destination' => $arrangement->destination
            ],
            'weather' => [
                'temperature' => $data['main']['temp'],
                'feels_like' => $data['main']['feels_like'],
                'weather' => $data['weather'][0]['description'],
                'humidity' => $data['main']['humidity']
            ]
        ]);
    }

    #[OA\Get(
        path: "/api/city-info",
        summary: "Informacije o gradu",
        description: "Vraca geografske informacije o gradu koristeci Open-Meteo Geocoding API.",
        tags: ["Grad"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "city", in: "query", required: true, schema: new OA\Schema(type: "string"), example: "Belgrade")
        ],
        responses: [
            new OA\Response(response: 200, description: "Uspesno vraceni podaci o gradu"),
            new OA\Response(response: 404, description: "Grad nije pronadjen"),
            new OA\Response(response: 422, description: "Greska validacije")
        ]
    )]
    public function cityInfo(Request $request){
        $validator = Validator::make($request->all(), [
            'city' => 'required|string'
        ]);
        
        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $city = $request->city;

        $response = Http::get('https://geocoding-api.open-meteo.com/v1/search', [
            'name' => $city,
            'count' => 1,
            'language' => 'en',
            'format' => 'json'
        ]);

        if ($response->failed()){
            return response()->json([
                'message' => 'City data not available'
            ], 500);
        }
        
        $data = $response->json();

        if(!isset($data['results'][0])){
            return response()->json([
                'message' => 'City not found'
            ], 404);
        }

        $cityData = $data['results'][0];

        return response()->json([
            'city' => $cityData['name'] ?? null,
            'country' => $cityData['country'] ?? null,
            'region' => $cityData['admin1'] ?? null,
            'population' => $cityData['population'] ?? null
        ]);
    }

    #[OA\Get(
        path: "/api/arrangements/{id}/city-info",
        summary: "Informacije o gradu aranzmana",
        description: "Vraca geografske informacije o destinaciji konkretnog aranzmana.",
        tags: ["Grad"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new OA\Response(response: 200, description: "Uspesno vraceni podaci o gradu"),
            new OA\Response(response: 404, description: "Aranzman ili grad nisu pronadjeni")
        ]
    )]
    public function cityInfoByArrangement($id){
        $arrangement = Arrangement::find($id);

        if(!$arrangement){
            return response()->json([
                'message' => 'Arrangement not found'
            ],404);
        }

        $city = $arrangement->destination;

        $response = Http::get('https://geocoding-api.open-meteo.com/v1/search', [
            'name' => $city,
            'count' => 1,
            'language' => 'en',
            'format' => 'json'
        ]);

        if($response->failed()){
            return response()->json([
                'message' => 'City data not available'
            ], 500);
        }

        $data = $response->json();

        if(!isset($data['results'][0])){
            return response()->json([
                'message' => 'City not found'
            ], 404);
        }

        $cityData = $data['results'][0];

        return response()->json([
            'arrangement' => [
                'id' => $arrangement->id,
                'title' => $arrangement->title,
                'destination' => $arrangement->destination
            ],
            'city_info' => [
                'city' => $cityData['name'] ?? null,
                'country' => $cityData['country'] ?? null,
                'region' => $cityData['admin1'] ?? null,
                'population' => $cityData['population'] ?? null
            ]
        ]);
    }

    #[OA\Get(
        path: "/api/arrangements/ratings",
        summary: "Ocene aranzmana",
        description: "Vraca listu svih aranzmana sa prosecnom ocenom i brojem recenzija, sortiranu po oceni.",
        tags: ["Aranzmani"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Uspesno vracene ocene"),
            new OA\Response(response: 401, description: "Neautorizovan pristup")
        ]
    )]
    public function ratings(){
        $data = DB::table('arrangements')
            ->leftJoin('reviews', 'arrangements.id', '=', 'reviews.arrangement_id')
            ->select(
                'arrangements.id',
                'arrangements.title',
                DB::raw('COUNT(reviews.id) as total_reviews'),
                DB::raw('ROUND(IFNULL(AVG(reviews.rating), 0), 2) as avg_rating')
            )
            ->groupBy('arrangements.id', 'arrangements.title')
            ->orderByDesc('avg_rating')
            ->get();

        return response()->json($data);
    }
}
