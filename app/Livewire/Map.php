<?php

namespace App\Livewire;

use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Map extends Component
{
    public function render()
    {
        $attendance = Attendance::with('user')->get();

        return view('livewire.map', ['attendances' => $attendance]);
    }
}
