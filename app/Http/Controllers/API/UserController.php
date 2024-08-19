<?php

namespace App\Http\Controllers\API;

use App\Helpers\LogHelper;
use App\Http\Controllers\Controller;
use Exception;

class UserController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke()
    {
        try {
            $user = auth()->user();

            if (!$user->image) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'No Image Found'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $user->getImageUrlAttribute(),
                // 'data' => $user->image_url,
                'message' => 'Successfully get Image'
            ]);
        } catch (Exception $e) {

            LogHelper::error('Error retrieving Image: ' . $e->getMessage(), [
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
