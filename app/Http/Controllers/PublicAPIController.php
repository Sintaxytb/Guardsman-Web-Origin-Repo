<?php

namespace App\Http\Controllers;

use App\Models\GuardsmanKeyset;
use Illuminate\Http\Request;

class PublicAPIController extends Controller
{
    private function checkScope(Request $request, String $scope) {
        $keysetHeader = json_decode($request->header("X-GUARDSMAN-KEYSET"));
        $keyset = new GuardsmanKeyset(
            $keysetHeader->id,
            $keysetHeader->user_id,
            $keysetHeader->name,
            $keysetHeader->key,
            $keysetHeader->scopes,
        );

        return $keyset->hasScope($scope);
    }

    public function getUser(Request $request, String $identifier) {
        if (!$this->checkScope($request, "users.read")) {
            return response([
                "success" => false,
                "message" => "ENOSCOPE"
            ], 400);
        }

        return app('App\Http\Controllers\StoredUsersController')->getUser($request, $identifier);
    }
}
