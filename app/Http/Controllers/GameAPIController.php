<?php

namespace App\Http\Controllers;

use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Imskyyc\RbxcloudPhp\MessagingService;

class GameAPIController extends Controller
{
    public function serverStarted(Request $request, String $jobId)
    {
        $masterToken = $request->header("Authorization");
        if ($masterToken != env("GUARDSMAN_MASTER_GAME_KEY")) {
            return response([
                "success" => false,
                "message" => "EBADTOKEN"
            ], 401);
        }

        $existingServer = DB::select("SELECT * FROM `servers` WHERE `job_id`=?", [$jobId]);
        if (isset($existingServer[0])) {
            return response([
                "success" => false,
                "message" => "ESERVEREXISTS"
            ], 400);
        }

        $randomBytes = random_bytes(32);
        $token = bin2hex($randomBytes);

        $body = json_decode($request->getContent());
        $isVip = $body->isVip;
        $gameName = $body->gameName;
        $placeId = $body->placeId;

        DB::insert("INSERT INTO `servers`(`job_id`, `token`, `game_name`, `place_id`, `is_vip`) VALUES(?, ?, ?, ?, ?);", [
            $jobId,
            $token,
            $gameName,
            $placeId,
            $isVip
        ]);

        $roles = DB::select("SELECT * FROM `roles`");

        return response([
            "success" => true,
            "token" => $token,
            "roles" => $roles,
            "punishment_types" => app('App\Http\Controllers\PunishmentsController')->getPunishmentTypes()
        ]);
    }

    public function getPlayer(Request $request, String $jobId, String $identifier)
    {
        $serverToken = $request->header("X-GUARDSMAN-SERVER-TOKEN");

        $serverData = DB::select("SELECT * FROM `servers` WHERE `job_id`=? LIMIT 1;", [$jobId]);

        if (!isset($serverData[0])) {
            return response([
                "success" => false,
                "message" => "ENOSERVER"
            ], 404);
        }

        if ($serverData[0]->token != $serverToken) {
            return response([
                "success" => false,
                "message" => "EBADTOKEN"
            ], 401);
        }

        return app('App\Http\Controllers\StoredUsersController')->getUser($request, $identifier);
    }

    public function createPlayer(Request $request, String $jobId, String $identifier)
    {
        $serverToken = $request->header("X-GUARDSMAN-SERVER-TOKEN");

        $serverData = DB::select("SELECT * FROM `servers` WHERE `job_id`=? LIMIT 1;", [$jobId]);

        if (!isset($serverData[0])) {
            return response([
                "success" => false,
                "message" => "ENOSERVER"
            ], 404);
        }

        if ($serverData[0]->token != $serverToken) {
            return response([
                "success" => false,
                "message" => "EBADTOKEN"
            ], 401);
        }

        $username = $request->input("username");

        if (!isset($username)) {
            return response([
                "success" => false,
                "message" => "EBADFIELDS"
            ], 400);
        }

        $existingUser = DB::select("SELECT * FROM `users` WHERE `roblox_id`=?", [
            $identifier
        ]);

        if (array_key_exists(0, $existingUser)) {
            return response([
                "success" => false,
                "message" => "EEXISTS"
            ], 409);
        }

        DB::insert("INSERT INTO `users`(`username`, `roblox_id`) VALUES(?, ?);", [
            $username,
            $identifier
        ]);

        return app('App\Http\Controllers\StoredUsersController')->getUser($request, $identifier);
    }

    public function submitHash(Request $request, String $jobId, String $identifier)
    {
        $serverToken = $request->header("X-GUARDSMAN-SERVER-TOKEN");

        $serverData = DB::select("SELECT * FROM `servers` WHERE `job_id`=? LIMIT 1;", [$jobId]);

        if (!isset($serverData[0])) {
            return response([
                "success" => false,
                "message" => "ENOSERVER"
            ], 404);
        }

        if ($serverData[0]->token != $serverToken) {
            return response([
                "success" => false,
                "message" => "EBADTOKEN"
            ], 401);
        }

        $user = app('App\Http\Controllers\StoredUsersController')->getUser($request, $identifier);
        $hash = $request->input("hash");
        $accountName = $request->input("account_name");

        if (empty($hash) || empty($accountName))
        {
            return response([
                "success" => false,
                "message" => "EBADFIELDS"
            ]);
        }

        $existingData = DB::select("SELECT * FROM `fingerprints` WHERE `user_id`=? AND `hash`=? AND `account_name`=?", [
            $user->roblox_id,
            $hash,
            $accountName
        ]);

        if (array_key_exists(0, $existingData)) {
            return response([
                "success" => true,
                "message" => "SDATAEXISTS"
            ], 200);
        }

        DB::insert("INSERT INTO `fingerprints` VALUES(?, ?, ?)", [
            $user->roblox_id,
            $hash,
            $accountName
        ]);

        $knownAccounts = app('App\Http\Controllers\StoredUsersController')->getAccounts($request, $user->roblox_id);
        $knownUsernames = array();

        foreach ($knownAccounts as $knownAccount) {
            foreach ($knownAccount["known_accounts"] as $knownUsername) {
                if (in_array($knownUsername, $knownUsernames)) continue;
                array_push($knownUsernames, $knownUsername);
            }
        }

        $usernames = join(", ", $knownUsernames);

        $dataUrl = env("GUARDSMAN_ALT_WEBHOOK");
        $json_data = json_encode([
            "content" => "A new hash was submitted: `$hash`\nAssociated accounts: $usernames"
        ]);

        $ch = curl_init($dataUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_exec($ch);
        // If you need to debug, or find out why you can't send message uncomment line below, and execute script.
        // echo $response;
        curl_close($ch);

        return response([
            "success" => true
        ], 200);
    }

    public function remoteExecute(Request $request, String $jobId)
    {
        $authUser = $request->user();
        $user = app('App\Http\Controllers\StoredUsersController')->getUser($request, $authUser->id);

        if (!in_array("development:remote-execute", $user->permissions)) {
            return response([
                "success" => false,
                "status" => "ENOPERMS"
            ]);
        }

        // message to roblox
        $code = $request->input("code");
        $allowedServices = $request->input("allowedServices");
        $publishingKeys = json_decode(env("GUARDSMAN_PUBLISHING_KEYS"));
        $publishingPlaces = json_decode(env("GUARDSMAN_PUBLISHING_PLACES"));

        foreach ($publishingPlaces as $index => $universeId) {
            $key = $publishingKeys[$index];

            MessagingService::publishMessage($universeId, "guardsmanapidata", $key, json_encode(array(
                "RemoteExecute",
                $jobId,
                $code,
                $allowedServices
            )));
        }

        return response([
            "success" => true
        ]);
    }

    public function closeServer(Request $request, String $jobId)
    {
        $authUser = $request->user();
        $user = app('App\Http\Controllers\StoredUsersController')->getUser($request, $authUser->id);

        if (!in_array("development:remote-execute", $user->permissions)) {
            return response([
                "success" => false,
                "status" => "ENOPERMS"
            ]);
        }

        // message to roblox
        $reason = $request->input("reason");

        $publishingKeys = json_decode(env("GUARDSMAN_PUBLISHING_KEYS"));
        $publishingPlaces = json_decode(env("GUARDSMAN_PUBLISHING_PLACES"));

        foreach ($publishingPlaces as $index => $universeId) {
            $key = $publishingKeys[$index];

            MessagingService::publishMessage($universeId, "guardsmanapidata", $key, json_encode(array(
                "CloseServer",
                $jobId,
                $reason,
                $authUser->username
            )));
        }

        return response([
            "success" => true
        ]);
    }

    public function updateServer(Request $request, String $jobId)
    {
        $serverToken = $request->header("X-GUARDSMAN-SERVER-TOKEN");

        $serverData = DB::select("SELECT * FROM `servers` WHERE `job_id`=?", [$jobId]);

        if (!isset($serverData[0])) {
            return response([
                "success" => false,
                "message" => "ENOSERVER"
            ], 404);
        }

        $serverData = $serverData[0];
        if ($serverData->token != $serverToken) {
            return response([
                "success" => false,
                "message" => "EBADTOKEN"
            ], 401);
        }

        $body = json_decode($request->getContent());
        $players = $body->players;

        if (!isset($players)) {
            $players = array();
        }

        $publishingKeys = json_decode(env("GUARDSMAN_PUBLISHING_KEYS"));
        $publishingPlaces = json_decode(env("GUARDSMAN_PUBLISHING_PLACES"));

        foreach ($players as $player) {
            $userData = app('App\Http\Controllers\StoredUsersController')->getUser($request, $player->roblox_id);

            foreach ($userData->punishments as $punishment) {
                if ($punishment->active == 1) {
                    foreach ($publishingPlaces as $index => $universeId) {
                        $key = $publishingKeys[$index];

                        MessagingService::publishMessage($universeId, "guardsmanapidata", $key, json_encode(array(
                            "RemovePunishedPlayerFromServer",
                            "all",
                            $player->roblox_id,
                            $punishment
                        )));
                    }

                    break;
                }
            }
        }

        DB::update("UPDATE `servers` SET `players`=?, `player_count`=?, `last_ping`=CURRENT_TIMESTAMP WHERE `job_id`=?", [
            json_encode($players),
            count($players),
            $jobId
        ]);

        return response([
            "success" => true
        ]);
    }

    public function deleteServer(Request $request, String $jobId)
    {
        $serverToken = $request->header("X-GUARDSMAN-SERVER-TOKEN");

        $serverData = DB::select("SELECT * FROM `servers` WHERE `job_id`=?", [$jobId]);

        if (!isset($serverData[0])) {
            return response([
                "success" => false,
                "message" => "ENOSERVER"
            ], 404);
        }

        $serverData = $serverData[0];
        if ($serverData->token != $serverToken) {
            return response([
                "success" => false,
                "message" => "EBADTOKEN"
            ], 401);
        }

        DB::delete("DELETE FROM `servers` WHERE `job_id`=?", [$jobId]);

        return response([
            "success" => true
        ]);
    }

    public function getServers(Request $request)
    {
        $servers = DB::select("SELECT * FROM `servers`");

        foreach ($servers as $server) {
            unset($server->token);
            $server->linked = !str_contains($server->job_id, "STUDIO-TEST-MODE");
        }

        return $servers;
    }

    public function punishUser(Request $request, String $jobId, String $id)
    {
        $serverToken = $request->header("X-GUARDSMAN-SERVER-TOKEN");

        $serverData = DB::select("SELECT * FROM `servers` WHERE `job_id`=? LIMIT 1;", [$jobId]);

        if (!isset($serverData[0])) {
            return response([
                "success" => false,
                "message" => "ENOSERVER"
            ], 404);
        }

        if ($serverData[0]->token != $serverToken) {
            return response([
                "success" => false,
                "message" => "EBADTOKEN"
            ], 401);
        }

        $punishmentType = $request->input("type");
        $punishmentReason = $request->input("reason");
        $punishmentExpires = strtotime($request->input("expires"));
        $punishmentEvidence = $request->input("evidence");
        $punishmentModerator = $request->input("moderator");

        if (is_int($request->input("expires"))) {
            $punishmentExpires = $request->input("expires");
        }

        if (empty($punishmentType) || empty($punishmentReason)) {
            return response([
                "success" => false,
                "status" => "EBADFIELDS"
            ], 400);
        }

        return app('App\Http\Controllers\PunishmentsController')->punishUser(
            $id,
            $punishmentModerator,
            $punishmentType,
            $punishmentReason,
            $punishmentExpires,
            $punishmentEvidence
        );
    }

    public function messageServer(Request $request, String $jobId, String $topic)
    {
        $authUser = $request->user();
        $user = app('App\Http\Controllers\StoredUsersController')->getUser($request, $authUser->id);

        if (!in_array("manage:servers", $user->permissions)) {
            return response([
                "success" => false,
                "status" => "ENOPERMS"
            ]);
        }

        // message to roblox
        $message = $request->input("message");
        $publishingKeys = json_decode(env("GUARDSMAN_PUBLISHING_KEYS"));
        $publishingPlaces = json_decode(env("GUARDSMAN_PUBLISHING_PLACES"));

        foreach ($publishingPlaces as $index => $universeId) {
            $key = $publishingKeys[$index];

            $curlHandle = curl_init();

            curl_setopt($curlHandle, CURLOPT_URL, "https://apis.roblox.com/messaging-service/v1/universes/$universeId/topics/$topic");
            curl_setopt($curlHandle, CURLOPT_POST, 1);
            curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array("x-api-key: $key", "Content-Type: application/json"));
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, json_encode(array(
                "message" => $message
            )));

            curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($curlHandle);

            curl_close($curlHandle);
        }

        return response([
            "success" => true
        ]);
    }

    public function exploitLog(Request $request, String $jobId)
    {
        $username =  $request->input("username");
        $userId =  $request->input("user_id");
        $code =  $request->input("code");
        $details =  $request->input("details");
        $action =  $request->input("action");

        DB::insert("INSERT INTO `ae_logs`(`user_id`, `code`, `action`, `details`) VALUES(?, ?, ?, ?)", [
            $userId,
            $code,
            json_encode($details),
            $action
        ]);
        
        $dataUrl = env("GUARDSMAN_EXPLOIT_WEBHOOK");
        /*
        {
      "type": "rich",
      "title": `Anti-Exploit Log`,
      "description": `{player} tripped check {code}!`,
      "color": 0xff4e4e,
      "fields": [
        {
          "name": `Game`,
          "value": `{game name}`,
          "inline": true
        },
        {
          "name": `Job Id`,
          "value": `{job Id}`,
          "inline": true
        },
        {
          "name": `Action Taken`,
          "value": `{action}`,
          "inline": true
        }
      ]
    }
        */
        $parsedDetails = array();

        foreach($details as $index=>$value)
        {
            if (is_array($value))
            {
                $value = json_encode($value);
            }

            array_push($parsedDetails, [
                "name" => $index,
                "value" => $value
            ]);
        }

        $gameName = $request->header("X-GUARDSMAN-GAME-NAME");
        $placeId = $request->header("X-GUARDSMAN-PLACE-ID");
        $dateTime = new DateTime();

        $json_data = json_encode([
            "embeds" => [
                [
                    "title" => "Anti-Exploit Log",
                    "description" => "$username tripped check $code.",
                    "color" => 0xff4e4e,
                    "fields" => array_merge([
                        [
                            "name" => "Game",
                            "value" => "[$gameName](https://www.roblox.com/games/$placeId/)",
                            "inline" => true,
                        ],
                        [
                            "name" => "Job ID",
                            "value" => $jobId,
                            "inline" => true
                        ],
                        [
                            "name" => "Action Taken",
                            "value" => $action,
                            "inline" => true
                        ]
                    ], $parsedDetails),

                    "footer" => [
                        "text" => "Guardsman Anti-Exploit"
                    ],

                    "timestamp" => $dateTime->format(DateTime::ATOM)
                ]
            ]
        ]);

        $ch = curl_init($dataUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_exec($ch);
        // If you need to debug, or find out why you can't send message uncomment line below, and execute script.
        // echo $response;
        curl_close($ch);

        return response([
            "success" => true
        ]);
    }
}
