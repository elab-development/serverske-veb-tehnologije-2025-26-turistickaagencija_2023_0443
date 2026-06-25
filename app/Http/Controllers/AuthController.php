<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: "/api/register",
        summary: "Registracija korisnika",
        description: "Kreira novi korisnički nalog i vraća token za autentifikaciju.",
        tags: ["Autentifikacija"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "email", "password"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Marko Markovic"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "marko@gmail.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "lozinka123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Uspesna registracija",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "user", type: "object"),
                        new OA\Property(property: "token", type: "string", example: "1|abcdef123456...")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Greska validacije")
        ]
    )]
    public function register(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6'
        ]);

        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        };
    
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    #[OA\Post(
        path: "/api/login",
        summary: "Prijava korisnika",
        description: "Proverava kredencijale i generise token za autentifikaciju.",
        tags: ["Autentifikacija"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "marko@gmail.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "lozinka123")
                ]
        )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Uspesna prijava",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "user", type: "object"),
                        new OA\Property(property: "token", type: "string", example: "1|abcdef123456...")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Pogresni kredencijali"),
            new OA\Response(response: 422, description: "Greska validacije")
        ]
    )]
    public function login(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        };
    
        $user = User::where('email', $request->email)->first();

        if(!$user || !Hash::check($request->password, $user->password)){
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }


    #[OA\Post(
        path: "/api/logout",
        summary: "Odjava korisnika",
        description: "Brise trenutni token i odjavljuje korisnika.",
        tags: ["Autentifikacija"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Uspesna odjava"),
            new OA\Response(response: 401, description: "Neautorizovan pristup")
        ]
    )]
    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    #[OA\Put(
        path: "/api/change-password",
        summary: "Promena lozinke",
        description: "Menja lozinku ulogovanog korisnika.",
        tags: ["Autentifikacija"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["current_password", "new_password"],
                properties: [
                    new OA\Property(property: "current_password", type: "string", format: "password", example: "stara123"),
                    new OA\Property(property: "new_password", type: "string", format: "password", example: "nova123")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Lozinka uspesno promenjena"),
            new OA\Response(response: 401, description: "Pogresna trenutna lozinka"),
            new OA\Response(response: 422, description: "Greska validacije")
        ]
    )]
    public function changePassword(Request $request){
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:6'
        ]);

        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if(!Hash::check($request->current_password, $user->password)){
            return response()->json([
                'message' => 'Current password is incorrect'
            ],401);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully'
        ]);
    }

    #[OA\Post(
        path: "/api/forgot-password",
        summary: "Zahtev za reset lozinke",
        description: "Generise token za resetovanje lozinke.",
        tags: ["Autentifikacija"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "marko@gmail.com")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Token uspesno generisan"),
            new OA\Response(response: 422, description: "Greska validacije")
        ]
    )]
    public function forgotPassword(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ]);

        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $token = Str::random(60);

        return response()->json([
            'message' => 'Password reset token generated',
            'token' => $token
        ]);
    }

    #[OA\Post(
        path: "/api/reset-password",
        summary: "Resetovanje lozinke",
        description: "Resetuje lozinku korisnika pomocu tokena.",
        tags: ["Autentifikacija"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "token", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "marko@gmail.com"),
                    new OA\Property(property: "token", type: "string", example: "abc123..."),
                    new OA\Property(property: "password", type: "string", format: "password", example: "nova123")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Lozinka uspesno resetovana"),
            new OA\Response(response: 404, description: "Korisnik nije pronadjen"),
            new OA\Response(response: 422, description: "Greska validacije")
        ]
    )]
    public function resetPassword(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'token' => 'required',
            'password' => 'required|min:6'
        ]);

        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $user = \App\Models\User::where('email', $request->email)->first();

        if(!$user){
            return response()->json([
                'message' => 'User not found'
            ],404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'message' => 'Password successfully reset'
        ]);
    }
}
