<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class UserController extends Controller
{
    public function index(Request $request){
        $paged = User::paginate(5);

        return response()->json([
            'current_page' => $paged->currentPage(),
            'data' => $paged->map(function ($user){
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role
                ];
            }),
            'total_pages' => $paged->lastPage(),
        ]);
    }

    public function changeRole(Request $request,$id){
        $validator = Validator::make($request->all(),[
            'role' => 'required|in:admin,manager,user'
        ]);

        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ],422);
        }

        $user = User::find($id);

        if(!$user){
            return response()->json([
                'message' => 'User not found'
            ],404);
        }

        if($request->user()->id === $user->id){
            return response()->json([
                'message' => 'You cannot change your own role'
            ], 403);
        }

        $user->role = $request->role;
        $user->save();

        return response()->json([
            'message' => 'Role updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role
            ]
        ]);
    }
}
