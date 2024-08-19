<?php

namespace App\Http\Controllers\API;

use App\Helpers\LogHelper;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Schedule;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    public function getAttendanceToday()
    {
        DB::enableQueryLog();
        try {
            $userId = auth()->user()->id;
            $today = now()->toDateString();
            $currentMonth = now()->month;

            $attendanceToday = Attendance::select('start_time', 'end_time')
                ->where('user_id', $userId)
                ->whereDate('created_at', $today)
                ->first();

            LogHelper::info('Get query Attendence where today', [
                'query' => DB::getQueryLog(),
                'user_id' => $userId,
            ]);

            $attendanceMonth = Attendance::select('start_time', 'end_time', 'created_at')
                ->where('user_id', $userId)
                ->whereMonth('created_at', $currentMonth)
                ->get()
                ->map(function ($record) {
                    return [
                        'start_time' => $record->start_time,
                        'end_time' => $record->end_time,
                        'date' => $record->created_at->toDateString(),
                    ];
                });

            LogHelper::info('Get query Attendence where month', [
                'query' => DB::getQueryLog(),
                'user_id' => $userId,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'today' => $attendanceToday,
                    'this_month' => $attendanceMonth,
                ],
                'message' => 'Successfully get attendance today'
            ]);
        } catch (Exception $e) {

            LogHelper::error('Error retrieving attendance today: ' . $e->getMessage(), [
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

    public function getAttendanceMonthYear($month, $year)
    {
        DB::enableQueryLog();

        $validator = Validator::make(['month' => $month, 'year' => $year], [
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020|max:' . date('Y')
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data' => $validator->errors(),
                'message' => 'Validation Error'
            ], 422);
        }

        try {
            $userId = auth()->user()->id;


            $attendanceList = Attendance::select('start_time', 'end_time', 'created_at')
                ->where('user_id', $userId)
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->get()
                ->map(function ($record) {
                    return [
                        'start_time' => $record->start_time,
                        'end_time' => $record->end_time,
                        'date' => $record->created_at->toDateString(),
                    ];
                });

            LogHelper::info('Get query Attendence where month and year', [
                'query' => DB::getQueryLog(),
                'user_id' => $userId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $attendanceList,
                'message' => 'Successfully get attendance by month by year'
            ]);
        } catch (Exception $e) {
            LogHelper::error('Error retrieving attendance month year: ' . $e->getMessage(), [
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


    public function store(Request $request)
    {
        DB::enableQueryLog();

        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric'
        ]);

        try {
            $userId = auth()->user()->id;

            $schedule = Schedule::where('user_id', $userId)->first();
            if (!$schedule) {
                LogHelper::warning('No Schedule Found', [
                    'user_id' => $userId,
                    'query' => DB::getQueryLog(),
                ]);
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'No Schedule Found '
                ]);
            }

            $today = Carbon::today()->format('Y-m-d');
            $approvedLeave = Leave::where('user_id', $userId)->where('status', 'approved')->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->exists();

            if ($approvedLeave) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'You are cannot to clock in, because you are on leave '
                ]);
            }

            $attendance = Attendance::where('user_id', $userId)->whereDate('created_at', Carbon::today())->first();
            if (!$attendance) {
                $attendance = Attendance::create([
                    'user_id' => $userId,
                    'schedule_latitude' => $schedule->office->latitude,
                    'schedule_longitude' => $schedule->office->longitude,
                    'schedule_start_time' => $schedule->shift->start_time,
                    'schedule_end_time' => $schedule->shift->end_time,
                    'start_latitude' => $request->latitude,
                    'start_longitude' => $request->longitude,
                    'start_time' => Carbon::now()->toTimeString(),
                ]);

                LogHelper::info('Create Attendance where user_id', [
                    'query' => DB::getQueryLog(),
                    'user_id' => $userId,
                ]);
            } else {
                $attendance->update([
                    'end_latitude' => $request->latitude,
                    'end_longitude' => $request->longitude,
                    'end_time' => Carbon::now()->toTimeString(),
                ]);

                LogHelper::info('Update Attendance where user_id', [
                    'query' => DB::getQueryLog(),
                    'user_id' => $userId,
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $attendance,
                'message' => 'Successfully store Attendace '
            ]);
        } catch (\Exception $e) {
            LogHelper::error('Error Store attendance: ' . $e->getMessage(), [
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
