<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Arrangement;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ArrangementResource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ArrangementController extends Controller
{
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

    public function show($id){
        $arrangement = Arrangement::find($id);
        if(!$arrangement){
            return response()->json([
                'message' => 'Arrangement not found'
            ], 404);
        }
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

    public function weather(Request $request){
        $validator = Validator::make($request->all(), [
            'destination' => 'required|string'
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
}
