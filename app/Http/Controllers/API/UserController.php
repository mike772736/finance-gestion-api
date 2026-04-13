<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show(Request $request)
    {
        return response()->json($request->user());
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . auth()->id(),
            'phone' => 'sometimes|nullable|string|max:20',
            'avatar' => 'sometimes|nullable|string',
        ]);

        $user = auth()->user();
        $user->update($request->only(['name', 'email', 'phone', 'avatar']));

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'user' => $user,
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();

        if (!\Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Le mot de passe actuel est incorrect',
            ], 422);
        }

        $user->update([
            'password' => $request->new_password,
        ]);

        return response()->json([
            'message' => 'Mot de passe mis à jour avec succès',
        ]);
    }
}
