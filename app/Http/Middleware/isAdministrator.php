<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class isAdministrator
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response) $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(Auth::user()->role_id != 1) {
            return response()->json([
                "error" => [
                    "code" => 403,
                    "message" => "Вы не администратор"
                ]
            ], 403, [ "Content-type" => "application/json" ]);
        }

        return $next($request);
    }
}
