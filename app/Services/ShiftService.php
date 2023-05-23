<?php

namespace App\Services;

use App\Models\UserShift;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;

class ShiftService
{
    public function index(): Collection
    {
        return Shift::all();
    }

    public function store($request)
    {
        return Shift::create([
            'start' => $request['start'],
            'end' => $request['end'],
        ]);
    }

    public function open($id)
    {
        $work_shift = Shift::find($id);
        if(!$work_shift) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такой смены нет'
                ]
            ], 404);
        }
        if($work_shift->active) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Эта смена уже открыта!'
                ]
            ], 404);
        }
        $work_shift->update([
            'active' => true
        ]);

        return $work_shift;
    }

    public function close($id)
    {
        $work_shift = Shift::find($id);
        if(!$work_shift) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такой смены нет'
                ]
            ], 404);
        }
        if(!$work_shift->active) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Эта смена уже закрыта!'
                ]
            ], 404);
        }
        $work_shift->update([
            'active' => false
        ]);

        return $work_shift;
    }

    public function add($id, $request)
    {
        return UserShift::create([
            'user_id' => $request['user_id'],
            'shift_id' => $id
        ]);
    }

    public function delete($id, $user_id): void
    {
        UserShift::where('shift_id', $id)->where('user_id', $user_id)->delete();
    }

}
