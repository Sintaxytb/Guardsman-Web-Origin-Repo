<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AuditLogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Imskyyc\RbxcloudPhp\MessagingService;

class PunishmentsController extends Controller
{
    private function getGUID()
    {
        mt_srand((float)microtime() * 10000); //optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45); // "-"
        $uuid = substr($charid, 0, 8);
        return $uuid;
    }

    public function getPunishments()
    {
        $punishments = DB::select("SELECT * FROM `punishments`");

        $moderatorIds = array();
        $userIds = array();

        foreach ($punishments as $punishment) {
            $moderator = null;
            $user = null;

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

            if (isset($userIds[$punishment->user])) {
                $user = $userIds[$punishment->user];
            } else {
                $foundUser = DB::select("SELECT * FROM `users` WHERE `id`=?", [$punishment->user]);
                if (isset($foundUser[0])) {
                    $user = $foundUser[0]->username;
                    $userIds[$punishment->user] = $foundUser[0]->username;
                } else {
                    $user = "Unknown.";
                }
            }

            $punishment->moderator = $moderator;
            $punishment->user = $user;
        }

        return response($punishments);
    }

    public function getPunishmentEvidence(Request $request, String $id)
    {
        return Storage::disk('public')->get('18371104.1.gz');
    }

    public function updateEvidence(Request $request, String $user, String $id)
    {
        $fileArray = $request->input('data');
        $images = 0;
        foreach ($fileArray as $file) {
            $images++;

            Storage::disk('public')->put("$id.$images.gz", $file);
        }

        return response([
            "success" => true
        ]);
    }

    public function getPunishmentsByModerator(Request $request, String $moderator)
    {
        $punishments = DB::select("SELECT * FROM `punishments` WHERE `moderator`=?;", [$moderator]);

        $moderatorIds = array();
        $userIds = array();

        foreach ($punishments as $punishment) {
            $moderator = null;
            $user = null;

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

            if (isset($userIds[$punishment->user])) {
                $user = $userIds[$punishment->user];
            } else {
                $foundUser = DB::select("SELECT * FROM `users` WHERE `id`=?", [$punishment->user]);
                if (isset($foundUser[0])) {
                    $user = $foundUser[0]->username;
                    $userIds[$punishment->user] = $foundUser[0]->username;
                } else {
                    $user = "Unknown.";
                }
            }

            $punishment->user_id = $punishment->user;
            $punishment->moderator = $moderator;
            $punishment->user = $user;
        }

        return response($punishments);
    }

    public function punishUser(string $identifier, String $moderator, String $type, String $reason, String $expires, $evidence)
    {
        $users = DB::select("SELECT * FROM `users` WHERE `id`=? LIMIT 1", [$identifier]);

        if ($expires == 0) {
            $expires = null;
        }

        if (!isset($users[0])) {
            return response([
                "success" => false,
                "status" => "ENOTFOUND"
            ], 404);
        }

        $user = $users[0];
        $punishmentId = $this->getGUID();

        DB::insert("INSERT INTO `punishments`(`id`, `moderator`, `user`, `action`, `reason`, `expires`) VALUES(?, ?, ?, ?, ?, ?)", [
            $punishmentId,
            $moderator,
            $user->id,
            $type,
            $reason,
            $expires
        ]);

        AuditLogController::submitLog($moderator, "USER_$type", "USER::$moderator added a $type to USER::$user->id for reason: $reason");

        // message to roblox
        $publishingKeys = json_decode(env("GUARDSMAN_PUBLISHING_KEYS"));
        $publishingPlaces = json_decode(env("GUARDSMAN_PUBLISHING_PLACES"));

        foreach ($publishingPlaces as $index => $universeId) {
            $key = $publishingKeys[$index];

            MessagingService::publishMessage($universeId, "guardsmanapidata", $key, json_encode(array(
                "RemovePunishedPlayerFromServer",
                "all",
                $user->roblox_id,
                array(
                    "id" => $punishmentId,
                    "moderator" => $moderator,
                    "user" => $user->username,
                    "action" => $type,
                    "reason" => $reason,
                    "active" => 1,
                    "expires" => $expires
                )
            )));
        }

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
        }

        return response([
            "success" => true,
            "id" => $punishmentId,
            "punishments" => $punishments
        ]);
    }

    public function punishUserByGuardsmanId(Request $request, String $userId)
    {
        $punishmentType = $request->input("type");
        $punishmentReason = $request->input("reason");
        $punishmentExpires = strtotime($request->input("expires"));
        $punishmentEvidence = $request->input("evidence");

        if (empty($punishmentType) || empty($punishmentReason)) {
            return response([
                "success" => false,
                "status" => "EBADFIELDS"
            ], 400);
        }

        $authUser = $request->user();
        $moderator = $authUser->id;

        return $this->punishUser($userId, $moderator, $punishmentType, $punishmentReason, $punishmentExpires, $punishmentEvidence);
    }

    public function disablePunishment(Request $request, String $username, String $punishmentId)
    {
        $path = $request->path();

        $users = DB::select("SELECT * FROM `users` WHERE `username`=?", [$username]);

        if (isset($users[0])) {
            $user = $users[0];
            $userId = $user->id;

            $authUser = $request->user();
            $moderator = $authUser->id;

            $punishment = DB::select("SELECT * FROM `punishments` WHERE `id`=? AND `user`=?", [
                $punishmentId,
                $userId
            ]);

            if (!isset($punishment[0])) {
                return response([
                    "success" => false,
                    "status" => "ENOTFOUND"
                ], 404);
            }

            $punishmentType = $punishment[0]->action;

            AuditLogController::submitLog($moderator, "USER_$punishmentType" . "_REMOVE", "USER::$moderator removed a $punishmentType from USER::$userId.");

            DB::update("UPDATE `punishments` SET `active`=0 WHERE `id`=?", [$punishmentId]);

            $punishments = DB::select("SELECT * FROM `punishments` WHERE `user`=?", [$userId]);
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
            }

            return response([
                "success" => true,
                "punishments" => $punishments
            ]);
        } else {
            return response([
                "success" => false,
                "status" => "ENOTFOUND"
            ], 404);
        }
    }

    public function deletePunishment(Request $request, String $username, String $punishmentId)
    {
        $authUser = $request->user();
        $moderator = $authUser->id;

        $user = DB::select("SELECT * FROM `users` WHERE `username`=?", [$username]);

        if (!isset($user[0])) {
            return response([
                "success" => false,
                "status" => "ENOUSER"
            ]);
        }

        $userId = $user[0]->id;

        AuditLogController::submitLog($authUser->id, "USER_PUNISHMENT_DELETE", "USER::$moderator permanently deleted a punishment from USER::$userId.");

        DB::delete("DELETE FROM `punishments` WHERE `id`=? AND `user`=?", [
            $punishmentId,
            $userId
        ]);

        $punishments = DB::select("SELECT * FROM `punishments` WHERE `user`=?", [$userId]);

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
        }

        return response([
            "success" => true,
            "punishments" => $punishments
        ]);
    }

    public function reinstatePunishment(Request $request, String $username, String $punishmentId)
    {
        $path = $request->path();

        $users = DB::select("SELECT * FROM `users` WHERE `username`=?", [$username]);

        if (isset($users[0])) {
            $user = $users[0];
            $userId = $user->id;

            $authUser = $request->user();
            $moderator = $authUser->id;

            $punishment = DB::select("SELECT * FROM `punishments` WHERE `id`=? AND `user`=?", [
                $punishmentId,
                $userId
            ]);

            if (!isset($punishment[0])) {
                return response([
                    "success" => false,
                    "status" => "ENOTFOUND"
                ], 404);
            }

            $punishmentType = $punishment[0]->action;

            AuditLogController::submitLog($moderator, "USER_$punishmentType" . "_REINSTATE", "USER::$moderator reinstated a $punishmentType from USER::$userId.");

            DB::update("UPDATE `punishments` SET `active`=1 WHERE `id`=?", [$punishmentId]);

            $punishments = DB::select("SELECT * FROM `punishments` WHERE `user`=?", [$userId]);
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
            }

            return response([
                "success" => true,
                "punishments" => $punishments
            ]);
        } else {
            return response([
                "success" => false,
                "status" => "ENOTFOUND"
            ], 404);
        }
    }


    public function getPunishmentTypes()
    {
        $punishmentTypes = DB::select("SELECT * FROM `punishment_types`");

        return $punishmentTypes;
    }
}
