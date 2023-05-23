<?php

namespace App\Http\Controllers\WorkShift;

use App\Http\Controllers\Controller;
use App\Services\ShiftService;

class ServiceController extends Controller
{
    public ShiftService $service;

    public function __construct(ShiftService $service){
        $this->service = $service;
    }
}
