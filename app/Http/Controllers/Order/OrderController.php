<?php

namespace App\Http\Controllers\Order;

use App\Http\Requests\Order\OrderRequest;
use App\Http\Requests\Order\StatusRequest;
use App\Http\Requests\Position\PositionRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderPosition;
use App\Models\Position;
use App\Models\Shift;
use App\Models\UserShift;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class OrderController extends ServiceController
{

    /**
     * Display orders in shift.
     */
    public function orders($id): JsonResponse
    {
        $orders = $this->service->orders();
        $shift = Shift::query()->find($id);
        if(!$shift) {
            return response()->json([
                'code' => 404,
                'message' => 'Такой смены не существует!'
            ], 404);
        }

        return response()->json([
            'data' => OrderResource::collection($orders),
        ]);
    }

    /**
     * Display orders in shift.
     */
    public function taken(): JsonResponse
    {
        $orders = $this->service->orders();
        $id = UserShift::select('shift_id')->where('user_id', Auth::id())->first();
        $shift = Shift::find($id);
        if(!$shift) {
            return response()->json([
                'code' => 404,
                'message' => 'Сотрудник не в смене!'
            ], 404);
        }

        return response()->json([
            'data' => OrderResource::collection($orders),
        ]);
    }

    /**
     * Display orders taken in shift.
     */
    public function index($id): JsonResponse
    {
        $orders = $this->service->index();
        $shift = Shift::where('id', $id);
        if(!$shift->first()) {
            return response()->json([
                'code' => 404,
                'message' => 'Такой смены не существует!'
            ], 404);
        }
        if(!$orders->first()) {
            return response()->json([
                'code' => 404,
                'message' => 'Принятых заказов нет'
            ], 404);
        }

        return response()->json([
            'data' => OrderResource::collection($orders),
        ]);
    }

    /**
     * Show order.
     */
    public function show($id): JsonResponse
    {
        $order = $this->service->show($id);
        if(!$order) {
            return response()->json([
                'code' => 404,
                'message' => 'Такого заказа нет!'
            ], 404);
        }

        return response()->json([
            'data' => $order
        ]);
    }

    /**
     * Add order in shift.
     */
    public function add_order(OrderRequest $request): JsonResponse
    {
        $work_shift = Shift::find($request['work_shift_id']);
        if(!$work_shift) {
            return response()->json([
                'code' => 404,
                'message' => 'Такой смены не существует!'
            ], 404);
        }
        $workers = $work_shift->users;
        foreach ($workers as $worker) {
            if($worker->role_id === Auth::user()->role_id) {
                $order = $this->service->add_order($request);
                return response()->json([
                    'data' => $order,
                ]);
            }
        }
        return response()->json([
            'code' => 404,
            'message' => 'Сотрудника нет в смене!!'
        ], 404);
    }

    /**
     * Add position in order.
     */
    public function add_position($id, PositionRequest $request): JsonResponse
    {
        $position = Position::find($request['menu_id']);
        $order = Order::find($id);
        $price_all = $order->price_all;
        if (!$order) {
            return response()->json([
                'code' => 404,
                'message' => 'Такого заказа нет!'
            ], 404);
        }
        if(!$position) {
            return response()->json([
                'code' => 404,
                'message' => 'Такой позиции нет в меню!'
            ], 404);
        }
        $order->update([
           'price_all' => ($price_all + ($position->price * $position->count))
        ]);
        $order = $this->service->add_position($id, $request);

        return response()->json([
            'data' => $order
        ]);
    }

    /**
     * Delete position in order.
     */
    public function delete_position($id, $position_id): JsonResponse
    {
        if(!Order::find($id)) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такого заказа не существует!'
                ]
            ], 404);
        }
        if(!OrderPosition::where('position_id', $position_id)->first()) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такой позиции в заказе нет!'
                ]
            ], 404);
        }
        $position = Position::find($position_id);
        $price_all = Order::find($id)->price_all - $position->price;
        Order::find($id)->update([
            'price_all' => $price_all
        ]);
        $this->service->delete_position($id, $position_id);

        return response()->json([
            'message' => 'Позиция удалена!'
        ]);
    }

    /**
     * Change status order.
     */
    public function change_status($id, StatusRequest $request): JsonResponse
    {
        if(!Order::find($id)) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такого заказа не существует!'
                ]
            ], 404);
        }
        if($request['status'] != 'Отменен' && $request['status'] != 'Оплачен') {
            return response()->json([
               'error' => [
                   'code' => 404,
                   'message' => 'Неверный статус заказа!'
               ]
            ]);
        }
        $this->service->change_status($id, $request);

        return response()->json([
            'message' => 'Статус изменен на '.$request['status']
        ]);
    }

    /**
     * Change status order by cook.
     */
    public function change_status_cook($id, StatusRequest $request): JsonResponse
    {
        $order = Order::find($id);
        if(!$order) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такого заказа не существует!'
                ]
            ], 404);
        }
        if($order->status == 'Отменен') {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Данный заказ был отменен!'
                ]
            ]);
        }
        if($request['status'] !== 'Готовиться' && $order->status === 'Принят') {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Невозможно изменить статус заказа!'
                ]
            ]);
        }
        if($request['status'] !== 'Готов' && $order->status === 'Готовиться') {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Невозможно изменить статус заказа!'
                ]
            ]);
        }
        $this->service->change_status($id, $request);

        return response()->json([
            'message' => 'Статус изменен на '.$request['status']
        ]);
    }
}
