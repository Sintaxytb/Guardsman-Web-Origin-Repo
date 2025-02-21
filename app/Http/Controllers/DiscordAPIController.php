<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Imskyyc\RbxcloudPhp\MessagingService;

class DiscordAPIController extends Controller
{
    private function getGUID()
    {
        mt_srand((float)microtime() * 10000); //optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45); // "-"
        $uuid = substr($charid, 0, 8);
        return $uuid;
    }

    public function onBotStartup(Request $request)
    {
        $clientGUID = $this->getGUID();

        DB::insert("INSERT INTO `discord_instances`(`id`, `client_id`) VALUES(?, ?)", [
            $clientGUID,
            $request->input("client_id")
        ]);

        return response([
            "success" => true,
            "client_guid" => $clientGUID
        ]);
    }

    public function onBotPing(Request $request)
    {
        DB::update("UPDATE `discord_instances` SET `last_ping` = CURRENT_TIMESTAMP WHERE `id`=? AND `client_id`=?", [
            $request->input("client_guid"),
            $request->input("client_id")
        ]);

        return response([
            "success" => true
        ]);
    }

    public function searchUser(Request $request, String $query)
    {
        $identifier = "%" . $query . "%";
        $users = DB::select("SELECT * FROM `users` WHERE `username` LIKE ? OR `roblox_id` LIKE ? OR `discord_id` LIKE ? LIMIT 10;", [
            $identifier,
            $identifier,
            $identifier
        ]);

        $parsedUsers = array();

        foreach ($users as $user) {
            array_push($parsedUsers, [
                "name" => $user->username,
                "value" => $user->username
            ]);
        }

        return response($parsedUsers);
    }

    private function getUserByFieldType(Request $request, String $fieldType, String $value)
    {
        $users = DB::select("SELECT * FROM `users` WHERE `$fieldType`=?", [$value]);

        if (!array_key_exists(0, $users)) {
            return false;
        }

        $user = $users[0];

        return app('App\Http\Controllers\StoredUsersController')->getUser($request, $user->id);
    }

    public function getUserByUsername(Request $request, String $username)
    {
        $user = $this->getUserByFieldType($request, "username", $username);

        if (!$user) {
            return response([
                "success" => false,
                "message" => "ENOUSER"
            ], 404);
        }

        return $user;
    }

    public function getUserByDiscordId(Request $request, String $userId)
    {
        $user = $this->getUserByFieldType($request, "discord_id", $userId);

        if (!$user) {
            return response([
                "success" => false,
                "message" => "ENOUSER"
            ], 404);
        }

        return $user;
    }

    public function getUserByGuardsmanId(Request $request, String $id)
    {
        $user = $this->getUserByFieldType($request, "id", $id);

        if (!$user) {
            return response([
                "success" => false,
                "message" => "ENOUSER"
            ], 404);
        }

        return $user;
    }

    public function punishUser(Request $request, String $id)
    {
        $punishmentType = $request->input("type");
        $punishmentReason = $request->input("reason");
        $punishmentExpires = $request->input("expires");
        $punishmentEvidence = $request->input("evidence");
        $punishmentModerator = $request->input("moderator");

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

    public function getGuildConfiguration(Request $request, String $guildId)
    {
        $guildSettings = DB::select("SELECT * FROM `configuration` WHERE `guild_id`=?", [$guildId]);

        if (!array_key_exists(0, $guildSettings)) {
            return response([
                "success" => false,
                "message" => "ENOGUILD"
            ], 404);
        }

        $parsedSettings = array();

        foreach ($guildSettings[0] as $key => $value) {
            $parsedSettings[$key] = $value;
        }

        return response($parsedSettings);
    }

    public function setGuildConfiguration(Request $request, String $guildId)
    {
        $configSettings = $request->input("settings");
        $configModeratorRoles = $request->input("moderator_roles");
        $configMuteRole = $request->input("muterole");

        $guildSettings = DB::select("SELECT * FROM `configuration` WHERE `guild_id`=?", [$guildId]);

        if (!array_key_exists(0, $guildSettings)) {
            DB::insert("INSERT INTO `configuration`(`guild_id`, `settings`, `moderator_roles`, `muterole`) VALUES(?, ?, ?, ?)", [
                $guildId,
                $configSettings,
                $configModeratorRoles,
                $configMuteRole
            ]);

            return response([
                "success" => true
            ]);
        }

        DB::update("UPDATE `configuration` SET `settings`=?, `moderator_roles`=?, `muterole`=? WHERE `guild_id`=?", [
            $configSettings,
            $configModeratorRoles,
            $configMuteRole,
            $guildId
        ]);

        return response([
            "success" => true
        ]);
    }
}
