<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\UserService;

class ServiceController extends Controller
{
    public UserService $service;

    public function __construct(UserService $service){
        $this->service = $service;
    }
}
