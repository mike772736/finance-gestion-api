<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            "name" => $request->name,
            "email" => $request->email,
            "password" => Hash::make($request->password)
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            "message" => "Utilisateur créé",
            "user" => $user,
            "token" => $token
        ]);
    }


    public function login(Request $request)
    {
        $user = User::where("email",$request->email)->first();

        if(!$user || !Hash::check($request->password,$user->password))
        {
            return response()->json([
                "message"=>"Email ou mot de passe incorrect"
            ],401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            "message"=>"Connexion réussie",
            "user"=>$user,
            "token"=>$token
        ]);
    }

}