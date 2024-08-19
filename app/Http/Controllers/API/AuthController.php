<?php

namespace App\Http\Controllers\API;

use App\Helpers\LogHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __invoke(Request $request)
    {
        DB::enableQueryLog();

        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            $user = User::where('email', $request->email)->first();

            LogHelper::info('GET query User where Email', [
                'query' => DB::getQueryLog(),
                'user_id' => $user->id,
            ]);

            if (!$user || !Hash::check($request->password, $user->password)) {
                LogHelper::warning('Login attempt failed', [
                    'email' => $request->email,
                ]);

                return response()->json([
                    'success' => false,
                    'data' => '',
                    'message' => 'Invalid email or password'
                ], 422);
            }

            $token = $user->createToken('API TOKEN')->plainTextToken;


            // Log informasi berhasil login
            LogHelper::info('Login successful', [
                'email' => $request->email,
                'user_id' => $user->id,
            ]);
            return response()->json([
                'success' => true,
                'data' => [
                    'acess_token' => $token,
                    'token_type' => 'Bearer',
                    'user' => $user
                ],
                'message' => 'Login Successfully'
            ]);
        } catch (\Exception $e) {
            // Mencatat log dengan pesan error dan nama file
            LogHelper::error('Error message: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Internal Server Error'
            ], 500);
        }
    }
}
