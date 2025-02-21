<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Imskyyc\RbxcloudPhp\GameService;

class EnsureGameAPIHasAuthorization
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
        $foundPlaces = DB::select("SELECT * FROM `groups` WHERE `games` LIKE ?", [
            '%' . $request->header("Roblox-Id") . '%'
        ]);

        if (!array_key_exists(0, $foundPlaces)) {
            return response([
                "success" => false,
                "message" => "ENOAUTH"
            ], 401);
        }

        $placeDetails = GameService::getPlaceDetails($request->header("Roblox-Id"), env("ROBLOX_USER_COOKIE"));
        $placeData = $foundPlaces[0];

        // verify owner of place id
        if ($placeDetails[0]->builderId != $placeData->group_id) {
            return response([
                "success" => false,
                "message" => "ENOAUTH"
            ], 401);
        }

        $request->headers->set("X-GUARDSMAN-GAME-NAME", $placeDetails[0]->name);
        $request->headers->set("X-GUARDSMAN-PLACE-ID", $placeDetails[0]->placeId);

        return $next($request);
    }
}
