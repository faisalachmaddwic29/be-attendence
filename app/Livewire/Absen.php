<?php

namespace App\Livewire;

use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Absen extends Component
{
    public $latitude;
    public $longitude;
    public $isInsideRadius = false;

    public function render()
    {
        $schedule = Schedule::where('user_id', Auth::user()->id)->first();
        $attendance = Attendance::where('user_id', Auth::user()->id)->whereDate('created_at', date('Y-m-d'))->first();
        return view('livewire.absen', ['schedule' => $schedule, 'isInsideRadius' => $this->isInsideRadius, 'attendance' => $attendance]);
    }

    public function store()
    {
        $this->validate([
            'latitude' => 'required',
            'longitude' => 'required',
        ]);


        $schedule = Schedule::where('user_id', Auth::user()->id)->first();

        $today = Carbon::today()->format('Y-m-d');
        $approvedLeave = Leave::where('user_id', Auth::user()->id)->where('status', 'approved')->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->exists();

        if ($approvedLeave) {
            session()->flash('error', 'Anda tidak dapat melakukan absensi karena sedang cuti');
            return;
        }

        if ($schedule) {
            $attendance = Attendance::where('user_id', Auth::user()->id)->whereDate('created_at', Carbon::today())->first();
            if (!$attendance) {
                $attendance = Attendance::create([
                    'user_id' => Auth::user()->id,
                    'schedule_latitude' => $schedule->office->latitude,
                    'schedule_longitude' => $schedule->office->longitude,
                    'schedule_start_time' => $schedule->shift->start_time,
                    'schedule_end_time' => $schedule->shift->end_time,
                    'start_latitude' => $this->latitude,
                    'start_longitude' => $this->longitude,
                    'start_time' => Carbon::now()->toTimeString(),
                ]);
            } else {
                $attendance->update([
                    'end_latitude' => $this->latitude,
                    'end_longitude' => $this->longitude,
                    'end_time' => Carbon::now()->toTimeString(),
                ]);
            }

            return redirect('admin/attendances');

            // return redirect()->route('absen', [
            //     'schedule' => $schedule,
            //     'isInsideRadius' => false,
            //     'attendance' => $attendance
            // ]);
        }
    }
}
