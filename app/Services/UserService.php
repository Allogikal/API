<?php

namespace App\Services;

use App\Http\Requests\User\UserRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class UserService
{
    public function index(): Collection
    {
        return User::all();
    }

    public function store(UserRequest $request)
    {
        $photo_file = $request['photo_file'];
        $path = null;
        if($photo_file){
            $path = url(Storage::disk('public')->put('photos', $photo_file));
        }
        return User::create([
            'name' => $request['name'],
            'surname' => $request['surname'],
            'email' => $request['email'],
            'patronymic' => $request['patronymic'],
            'login' => $request['login'],
            'password' => $request['password'],
            'photo_file' => $path,
            'role_id' => $request['role_id'],
        ]);
    }

    public function show($id)
    {
        $user = User::find($id);
        if(!$user){
            return false;
        }

        return $user;
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if(!$user){
            return false;
        }
        if($user->photo_file){
            Storage::disk('public')->delete($user->photo_file);
        }

        return $user->delete();
    }
}
