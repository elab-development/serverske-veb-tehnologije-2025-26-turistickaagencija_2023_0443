<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    #[OA\Get(
        path: "/api/users",
        summary: "Lista svih korisnika",
        description: "Vraca paginiranu listu korisnika. Dostupno samo administratorima.",
        tags: ["Korisnici"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Uspesno vracena lista korisnika"),
            new OA\Response(response: 401, description: "Neautorizovan pristup"),
            new OA\Response(response: 403, description: "Nemate dozvolu")
        ]
    )]
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

    #[OA\Put(
        path: "/api/users/{id}/role",
        summary: "Promena uloge korisnika",
        description: "Menja ulogu korisnika. Dostupno samo administratorima. Administrator ne moze menjati svoju ulogu.",
        tags: ["Korisnici"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1)
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["role"],
                properties: [
                    new OA\Property(property: "role", type: "string", enum: ["admin", "manager", "user"], example: "manager")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Uloga uspesno promenjena"),
            new OA\Response(response: 403, description: "Nemate dozvolu ili pokusavate da promenite svoju ulogu"),
            new OA\Response(response: 404, description: "Korisnik nije pronadjen"),
            new OA\Response(response: 422, description: "Greska validacije")
        ]
    )]
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
