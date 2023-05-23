<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPosition;
use App\Models\OrderUser;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class OrderService
{
    public function orders(): Collection
    {
        return Order::all();
    }

    public function index(): Collection
    {
        return Order::all()->where('status', 'Принят');
    }

    public function show($id)
    {
        return Order::find($id);
    }

    public function add_order($request)
    {
        $order = Order::create([
            'table' => $request['table_id'],
            'number_of_person' => $request['number_of_person'],
            'status' => 'Принят',
            'price_all' => 0,
        ]);
        OrderUser::create([
            'order_id' => $order->id,
            'user_id' => Auth::user()->id
        ]);

        return $order;
    }

    public function add_position($id, $request) {
        return OrderPosition::create([
            'order_id' => $id,
            'position_id' => $request['menu_id']
        ]);
    }

    public function delete_position($id, $position_id): void
    {
        OrderPosition::where('position_id', $position_id)->delete();
    }

    public function change_status($id, $request)
    {
        return Order::find($id)->update([
            'status' => $request['status']
        ]);
    }
}
