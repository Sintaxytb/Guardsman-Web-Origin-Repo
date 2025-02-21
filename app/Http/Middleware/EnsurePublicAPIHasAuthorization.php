<?php

namespace App\Http\Middleware;

use App\Models\GuardsmanKeyset;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnsurePublicAPIHasAuthorization
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
        $guardsmanId = $request->header("X-GUARDSMAN-USER-ID");

        $matchingKeys = DB::select("SELECT * FROM `api_keys` WHERE `key`=? AND `user_id`=? LIMIT 1;", [
            $request->header("X-GUARDSMAN-API-KEY"),
            $guardsmanId
        ]);

        if (!(array_key_exists(0, $matchingKeys))) {
            return response([
                "success" => false,
                "message" => "ENOAUTH"
            ], 401);
        }

        $matchingKey = $matchingKeys[0];

        $keyset = new GuardsmanKeyset(
            $matchingKey->id,
            $matchingKey->user_id,
            $matchingKey->name,
            $matchingKey->key,
            $matchingKey->scopes
        );

        $request->headers->set("X-GUARDSMAN-KEYSET", json_encode($keyset));

        return $next($request);
    }
}
