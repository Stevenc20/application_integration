<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SignatureController extends Controller
{
    public function verifyPin(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'pin' => 'required|string'
        ]);

        $user = User::find($request->user_id);

        if (!Hash::check($request->pin, $user->password)) {
            return response()->json(['success' => false, 'message' => 'PIN salah.'], 403);
        }

        $signature = $user->signature;

        return response()->json([
            'success' => true,
            'signature' => $signature,
            'user_name' => $user->name
        ]);
    }

    public function getSignature(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);
        $signature = $user->signature;

        return response()->json([
            'success' => true,
            'signature' => $signature,
            'user_name' => $user->name
        ]);
    }

    public function saveMaster(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'signature' => 'required|string',
        ]);

        $user = User::find($request->user_id);
        $user->signature = $request->signature;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Tanda tangan berhasil disimpan.'
        ]);
    }
}
