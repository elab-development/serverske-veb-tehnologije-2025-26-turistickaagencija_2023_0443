<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Arrangement;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ArrangementResource;

class ArrangementController extends Controller
{
    public function index(Request $request) 
    {
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
        return response()->json([
            'current_page' =>$paged->currentPage(),
            'data' => ArrangementResource::collection($paged->items()),
            'total_pages' => $paged->lastPage()
        ]);
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
}
