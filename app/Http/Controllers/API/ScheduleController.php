<?php

namespace App\Http\Controllers\API;

use App\Helpers\LogHelper;
use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\Schedule;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    public function getList()
    {
        DB::enableQueryLog();
        try {
            $userId = auth()->user()->id;

            $schedule = Schedule::with(['office', 'shift'])
                ->where('user_id', $userId)
                ->first();

            LogHelper::info('Get query Schedule where user_id', [
                'query' => DB::getQueryLog(),
                'user_id' => $userId,
            ]);

            $today = Carbon::today()->format('Y-m-d');
            $approvedLeave = Leave::where('user_id', $userId)->where('status', 'approved')->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->exists();

            if ($approvedLeave) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'You are cannot to clock in, because you are on leave '
                ]);
            }


            if ($schedule->is_banned) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'You are banned, please contact admin'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $schedule,
                'message' => 'Successfully get schedule'
            ]);
        } catch (Exception $e) {

            LogHelper::error('Error retrieving schedule: ' . $e->getMessage(), [
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


    public function banned()
    {
        DB::enableQueryLog();
        try {
            $userId = auth()->user()->id;

            $schedule = Schedule::where('user_id', $userId)
                ->first();

            LogHelper::info('Get query Schedule where user_id', [
                'query' => DB::getQueryLog(),
                'user_id' => $userId,
            ]);

            if (!$schedule) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'No Schedule Found'
                ]);
            }

            $schedule->update([
                'is_banned' => true,
            ]);;

            return response()->json([
                'success' => true,
                'data' => $schedule,
                'message' => 'Successfully banned schedule'
            ]);
        } catch (Exception $e) {

            LogHelper::error('Error retrieving schedule: ' . $e->getMessage(), [
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
