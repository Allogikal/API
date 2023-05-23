<?php

namespace App\Http\Controllers\WorkShift;

use App\Http\Requests\WorkShift\addUserRequest;
use App\Http\Requests\WorkShift\ShiftRequest;
use App\Http\Resources\ShiftResource;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserShift;
use Illuminate\Http\JsonResponse;

class ShiftController extends ServiceController
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $work_shifts = $this->service->index();

        return response()->json([
            'data' => ShiftResource::collection($work_shifts),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ShiftRequest $request):JsonResponse
    {
        $work_shift = $this->service->store($request);
        if ($work_shift->end <= $work_shift->start) {
            return response()->json([
                'code' => 403,
                'message' => 'Неверные временные рамки!'
            ], 403);
        }

        return response()->json([
            'id' => $work_shift->id,
            'start' => $work_shift->start,
            'end' => $work_shift->end,
        ], 201);
    }

    /**
     * Open shift.
     */
    public function open($id):JsonResponse
    {
        $work_shift = $this->service->open($id);

        return response()->json([
            'data' => $work_shift
        ]);
    }

    /**
     * Close shift.
     */
    public function close($id):JsonResponse
    {
        $work_shift = $this->service->close($id);

        return response()->json([
            'data' => $work_shift
        ]);
    }

    /**
     * Add user to shift.
     */
    public function add($id, addUserRequest $request):JsonResponse
    {
        $shift = Shift::find($id);
        if (!$shift) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такой смены не существует!'
                ]
            ], 404);
        }
        if($shift->users->where('id', $request['user_id'])->first()) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такой пользователь уже в смене!'
                ]
            ], 404);
        }
        $user = User::find($request['user_id']);
        if (!$user) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такого пользователя не существует!'
                ]
            ], 404);
        }
        $this->service->add($id, $request);

        $user->update(['status' => 'working']);
        return response()->json([
            'id_user' => $request['user_id'],
            'status' => 'Сотрудник на смене'
        ]);
    }

    /**
     * Delete user to shift.
     */
    public function delete($id, $user_id):JsonResponse
    {
        $shift = Shift::find($id);
        if (!$shift) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такой смены нет!'
                ]
            ], 404);
        }
        $user = UserShift::where('user_id', $user_id)->first();
        if (!$user) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такой пользователь не в смене!'
                ]
            ], 404);
        }
        $this->service->delete($id, $user_id);
        $user->update(['status' => 'not working']);

        return response()->json([
            'message' => 'Сотрудник удален из смены'
        ]);
    }

}
