<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class StoredUsersController extends Controller
{
    public function redis_test()
    {
        try {
            $redis = Redis::connect('127.0.0.1', 3306);
            return true;
        } catch (\Predis\Connection\ConnectionException $e) {
            return false;
        }
    }

    public function getThumbnail(Request $request, String $userId)
    {
        function fetchThumbnail($userId)
        {
            $thumbnailData = file_get_contents("https://thumbnails.roblox.com/v1/users/avatar-headshot?userIds=$userId&size=110x110&format=Jpeg&isCircular=false");
            return json_decode($thumbnailData, true);
        }

        $redisEnabled = $this->redis_test();

        if (!$redisEnabled) {
            return fetchThumbnail($userId);
        }

        $cachedThumbnail = Redis::get("thumbnail:$userId");
        $ctime = time();

        if (isset($cachedThumbnail)) {
            $cachedData = json_decode($cachedThumbnail, true);

            if (isset($cachedData["fetch_time"]) && ($ctime < ($cachedData["fetch_time"]) + 3600)) {
                $cachedData["cached"] = true;
                return $cachedData;
            }

            $thumbnailData = fetchThumbnail($userId);
            $thumbnailData["fetch_time"] = $ctime;

            Redis::set("thumbnail:$userId", json_encode($thumbnailData));

            return $thumbnailData;
        } else {
            $thumbnailData = fetchThumbnail($userId);
            $thumbnailData["fetch_time"] = $ctime;

            Redis::set("thumbnail:$userId", json_encode($thumbnailData));

            return $thumbnailData;
        }
    }

    public function searchUser(Request $request, String $identifier)
    {
        $identifier = "%" . $identifier . "%";
        $users = DB::select("SELECT * FROM `users` WHERE `username` LIKE ? OR `roblox_id` LIKE ? OR `discord_id` LIKE ? LIMIT 10;", [
            $identifier,
            $identifier,
            $identifier
        ]);

        $parsedUsers = array();

        foreach ($users as $user) {
            array_push($parsedUsers, [
                "id" => $user->id,
                "title" => $user->username,
                "category" => "Users",
                "icon" => "tabler-user",
                "url" => [
                    "name" => "player-management",
                    "query" => [
                        "search" => $user->username
                    ]
                ],
            ]);
        }

        return response($parsedUsers);
    }

    public function updateUser(Request $request, String $id)
    {
        $user = app('App\Http\Controllers\AuthController')->user($request);

        $userData = (object) $request->input("data");
        $profile = app('App\Http\Controllers\StoredUsersController')->getUser($request, $id);
        $position = 0;

        $roles = DB::select("SELECT * FROM `roles`");

        foreach ($roles as $role) {
            $hasRole = in_array($role->name, $userData->roles);

            if ($hasRole and $role->position > $position) {
                $position = $role->position;
                $role->permissions = json_decode($role->permissions);
                $user->role = $role;
            }
        }

        if ($position >= $user->position || $profile->position >= $user->position) {
            return response([
                "success" => false,
                "message" => "ENOPERMS"
            ], 401);
        }

        DB::update("UPDATE `users` SET `username`=?, `roblox_id`=?, `discord_id`=?, `roles`=? WHERE `id`=?", [
            $userData->username,
            $userData->roblox_id,
            $userData->discord_id,
            json_encode($userData->roles),
            $id
        ]);

        // if (!empty($userData->game_data)) {
        //     foreach ($userData->game_data as $key=>$game) {
        //         $existingData = DB::select("SELECT * FROM `game_data` WHERE `user_id`=? AND `game_name`=?", [
        //             $userData->id,
        //             $key
        //         ]);

        //         if (isset($existingData[0])) {
        //             DB::update("UPDATE `game_data` SET `game_data`=? WHERE `user_id`=? AND `game_name`=?", [
        //                 json_encode($game),
        //                 $userData->id,
        //                 $key
        //             ]);
        //         } else {
        //             DB::insert("INSERT INTO `game_data`(`user_id`, `game_name`, `game_data`) VALUES(?, ?, ?)", [
        //                 $userData->id,
        //                 $key,
        //                 json_encode($game),
        //             ]);
        //         }
        //     }
        // }

        return response([
            "success" => true
        ]);
    }

    public function getUser(Request $request, String $identifier)
    {
        $users = DB::select("SELECT * FROM `users` WHERE `username`=? OR `roblox_id`=? OR `discord_id`=? OR `id`=? LIMIT 1", [
            $identifier, $identifier, $identifier, $identifier
        ]);


        if (isset($users[0])) {
            $user = $users[0];

            unset($user->password);
            unset($user->remember_token);

            // fetch punishments
            $punishments = DB::select("SELECT * FROM `punishments` WHERE `user`=?", [$user->id]);

            $moderatorIds = array();

            foreach ($punishments as $punishment) {
                $moderator = null;

                if (isset($moderatorIds[$punishment->moderator])) {
                    $moderator = $moderatorIds[$punishment->moderator];
                } else {
                    $foundModerator = DB::select("SELECT * FROM `users` WHERE `id`=?", [$punishment->moderator]);
                    if (isset($foundModerator[0])) {
                        $moderator = $foundModerator[0]->username;
                        $moderatorIds[$punishment->moderator] = $foundModerator[0]->username;
                    } else {
                        $moderator = "Unknown.";
                    }
                }

                $punishment->moderator = $moderator;
                $punishment->user = $user->username;
            }

            $user->punishments = $punishments;

            // fetch game data
            $gameData = DB::select("SELECT * FROM `game_data` WHERE `user_id`=?", [$user->roblox_id]);
            $user->game_data = $gameData;

            $rollbackPoints = DB::select("SELECT * FROM `data_rollback_points` WHERE `user_id`=?", [$user->roblox_id]);
            $user->rollback_points = $rollbackPoints;

            // fetch role
            $user->roles = json_decode($user->roles);

            // fetch permissions
            // get user groups
            $user->group_roles = json_decode($user->group_roles);
            $groupRoles = array();

            foreach ($user->group_roles as $groupId => $roleIds) {
                $roles = array();
                foreach ($roleIds as $roleId) {
                    $role = DB::select("SELECT * FROM `group_roles` WHERE `group_id`=? AND `id`=? LIMIT 1;", [$groupId, $roleId]);
                    if (!array_key_exists(0, $role)) continue;

                    array_push($roles, $role[0]);
                }

                $groupRoles[$groupId] = $roles;
            }

            $user->group_roles = $groupRoles;

            $permissionsList = array();
            foreach ($user->roles as $role) {
                $roleQueryResult = DB::select("SELECT * FROM `roles` WHERE `name`=?", [$role]);

                if (!array_key_exists(0, $roleQueryResult)) continue;
                $roleData = $roleQueryResult[0];
                $permissions = json_decode($roleData->permissions);

                foreach ($permissions as $permission => $enabled) {
                    if (!array_key_exists($permission, $permissionsList)) {
                        $permissionsList[$permission] = $enabled;
                    } else if ($permissionsList[$permission] == false && $enabled == true) {
                        $permissionsList[$permission] = true;
                    }
                }
            }


            foreach ($user->group_roles as $groupId => $groupRoles) {
                foreach ($groupRoles as $groupRole) {
                    $groupRole->members = json_decode($groupRole->members);
                    $groupRole->permissions = json_decode($groupRole->permissions);

                    foreach ($groupRole->permissions as $permissionNode => $enabled) {
                        $permissionsList[$permissionNode] = $enabled;
                    }
                }
            }

            $user->permissions = $permissionsList;

            // set position
            $roles = DB::select("SELECT * FROM `roles`");
            $position = 0;

            foreach ($roles as $role) {
                $hasRole = in_array($role->name, $user->roles);

                if ($hasRole and $role->position > $position) {
                    $position = $role->position;
                    $role->permissions = json_decode($role->permissions);
                    $user->role = $role;
                }
            }

            $user->position = $position;

            return $user;
        } else {
            return response([
                "success" => false,
                "status" => "ENOTFOUND"
            ], 404);
        }
    }

    public function getAccounts(Request $request, String $id)
    {
        $knownAccounts = array();
        $userHashes = array();
        $fingerprints = DB::select("SELECT * FROM `fingerprints` WHERE `user_id`=?", [$id]);

        function findUserHashes($userHashes, $fingerprint)
        {
            $fingerprints = DB::select("SELECT * FROM `fingerprints` WHERE `hash`=?", [$fingerprint]);

            foreach ($fingerprints as $fingerprint) {
                if (in_array($fingerprint->hash, $userHashes)) {
                    continue;
                }

                array_push($userHashes, $fingerprint->hash);
            }

            return $userHashes;
        }

        foreach ($fingerprints as $fingerprint) {
            $userHashes = findUserHashes($userHashes, $fingerprint->hash);
        }

        foreach ($userHashes as $hash) {
            $foundFingerprints = DB::select("SELECT * FROM `fingerprints` WHERE `hash`=?", [$hash]);
            $accounts = array();

            foreach ($foundFingerprints as $foundFingerprint) {
                array_push($accounts, $foundFingerprint->account_name);
            }

            array_push($knownAccounts, array(
                "hash" => $hash,
                "known_accounts" => $accounts
            ));
        }

        return $knownAccounts;
    }
}
