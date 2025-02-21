<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PanelAdministrationController extends Controller
{
    public function getAdministrators() {
        $nonPlayers = DB::select("SELECT * FROM `users` WHERE `roles` != '[\"Player\"]';");
        $administrators = array();

        foreach($nonPlayers as $user) {
            unset($user->password);
            unset($user->remember_token);

            array_push($administrators, $user);
        }

        return response($administrators);
    }

    public function getShout() {
        $shoutExists = file_exists("shout.json");
        if (!$shoutExists) {
            return response([]);
        }

        $shoutData = json_decode(file_get_contents("shout.json"));

        return response([
            "success" => true,
            "shouter_id" => $shoutData->shouter_id,
            "shouter_name" => $shoutData->shouter_name,
            "shout" => $shoutData->shout
        ]);
    }

    public function updateShout(Request $request) {
        $shoutText = $request->input("shout");
        $user = $request->user();
        $shoutFile = fopen("shout.json", "w");

        fwrite($shoutFile, json_encode([
            "shouter_id" => $user->roblox_id,
            "shouter_name" => $user->username,
            "shout" => $shoutText
        ]));

        fclose($shoutFile);

        return response(["success" => true]);
    }
}
