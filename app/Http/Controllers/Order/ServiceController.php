<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Services\OrderService;

class ServiceController extends Controller
{
    public OrderService $service;

    public function __construct(OrderService $service){
        $this->service = $service;
    }
}
