<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveApiUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->header('X-User-Id');
        $user = $userId === null ? null : User::find($userId);
        if ($user === null) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }
        $request->setUserResolver(static fn (): User => $user);

        return $next($request);
    }
}
