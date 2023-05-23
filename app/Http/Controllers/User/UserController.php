<?php

namespace App\Http\Controllers\User;

use App\Http\Requests\User\UserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class UserController extends ServiceController
{

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $users = $this->service->index();

        return response()->json([
            'data' => UserResource::collection($users),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request):JsonResponse
    {
        $user = $this->service->store($request);

        return response()->json([
            'id' => $user->id,
            'status' => 'Сотрудник заведен',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        $user = $this->service->show($id);
        if (!$user) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такого пользователя не существует'
                ]
            ], 404);
        }

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        $user = $this->service->destroy($id);
        if (!$user) {
            return response()->json([
                'error' => [
                    'code' => 404,
                    'message' => 'Такого пользователя не существует'
                ]
            ], 404);
        }

        return response()->json([
            'message' => 'Сотрудник уволен'
        ], 203);
    }
}
