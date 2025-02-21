<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureDiscordAPIHasAuthorization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $envKey = env("GUARDSMAN_DISCORD_TOKEN");

        $sentKey = $request->header("Authorization");

        if ($sentKey != $envKey) {
            return response([
                "success" => false,
                "message" => "ENOAUTH"
            ], 401);
        }

        return $next($request);
    }
}
