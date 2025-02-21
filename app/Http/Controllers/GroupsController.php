<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupsController extends Controller
{
    public function getGroup(Request $request, String $id)
    {
        $groups = DB::select("SELECT * FROM `groups` WHERE `id`=?", [$id]);

        if (!array_key_exists(0, $groups)) {
            return response([
                "success" => false,
                "message" => "ENOGROUP"
            ], 404);
        }

        $group = $groups[0];
        $roles = DB::select("SELECT * FROM `group_roles` WHERE `group_id`=?", [$id]);

        foreach ($roles as $role) {
            $role->permissions = json_decode($role->permissions);
            $role->members = json_decode($role->members);
        }

        $group->roles = $roles;
        $group->users = json_decode($group->users);
        $group->games = json_decode($group->games);
        $group->guilds = json_decode($group->guilds);

        // fetch permissions
        $permissions = DB::select("SELECT * FROM `group_permissions` WHERE `group_id`=?", [
            $group->id
        ]);

        $group->permissions = $permissions;

        // fetch users
        foreach ($group->users as $index => $userId) {
            $userData = DB::select("SELECT * FROM `users` WHERE `id`=?", [$userId]);
            if (!array_key_exists(0, $userData)) continue;

            $group->users[$index] = app('App\Http\Controllers\StoredUsersController')->getUser($request, $userId);
        }

        return $group;
    }

    public function getGroups(Request $request)
    {
        $groups = DB::select("SELECT * FROM `groups`");

        foreach ($groups as $index => $group) {
            $groups[$index] = $this->getGroup($request, $group->id);
        }

        return $groups;
    }

    public function createGroup(Request $request)
    {
        $groupName = $request->input("groupName");
        $groupId = $request->input("groupId");

        if (!isset($groupName) || !isset($groupId)) {
            return response([
                "success" => false,
                "message" => "EBADFIELDS"
            ], 400);
        }

        $existingGroup = DB::select("SELECT * FROM `groups` WHERE `group_id`=?", [$groupId]);

        if (array_key_exists(0, $existingGroup)) {
            return response([
                "success" => false,
                "message" => "EEXISTS"
            ], 409);
        }

        DB::insert("INSERT INTO `groups`(`group_name`, `group_id`) VALUES(?, ?)", [
            $groupName,
            $groupId
        ]);

        return response([
            "success" => true
        ], 200);
    }

    public function deleteGroup(Request $request, String $id)
    {
        $existingGroup = DB::select("SELECT * FROM `groups` WHERE `id`=?", [$id]);

        if (!array_key_exists(0, $existingGroup)) {
            return response([
                "success" => false,
                "message" => "ENOTFOUND"
            ], 404);
        }

        DB::delete("DELETE FROM `groups` WHERE `id`=?", [$id]);

        DB::delete("DELETE FROM `group_roles` WHERE `group_id`=?", [$id]);

        $usersWithGroups = DB::select('SELECT * FROM `users` WHERE `group_roles` != "{}"');

        foreach ($usersWithGroups as $user) {
            $groupRoles = json_decode($user->group_roles, true);

            if (!array_key_exists($id, $groupRoles)) continue;
            unset($groupRoles[$id]);

            $encodedRoles = json_encode($groupRoles);
            if ($encodedRoles == "[]") $encodedRoles = "{}";

            DB::update("UPDATE `users` SET `group_roles`=? WHERE `id`=?", [
                $encodedRoles,
                $user->id
            ]);
        }

        return response([
            "success" => true,
            "groups" => $this->getGroups($request)
        ], 200);
    }

    public function updateGroupUsers(Request $request, String $id)
    {
        $users = $request->input("users");

        if (!isset($users)) {
            return response([
                "success" => false,
                "message" => "EBADFIELDS"
            ], 400);
        }

        $group = DB::select("SELECT * FROM `groups` WHERE `id`=?", [$id]);

        if (!array_key_exists(0, $group)) {
            return response([
                "success" => false,
                "message" => "ENOGROUP"
            ], 404);
        }

        $groupRoles = DB::select("SELECT * FROM `group_roles` WHERE `group_id`=?", [$id]);
        foreach ($groupRoles as $groupRole) {
            $members = json_decode($groupRole->members, true);

            foreach ($members as $index => $userId) {
                if (in_array($userId, $users)) continue;

                $userData = DB::select("SELECT * FROM `users` WHERE `id`=?", [$userId]);
                if (!array_key_exists(0, $userData)) {
                    unset($members[$index]);
                    continue;
                }

                $userRoles = json_decode($userData[0]->group_roles, true);
                if (array_key_exists($id, $userRoles)) {
                    unset($userRoles[$id]);

                    $userRoles = json_encode($userRoles);
                    if ($userRoles == "[]") {
                        $userRoles = "{}";
                    }

                    DB::update("UPDATE `users` SET `group_roles`=? WHERE `id`=?", [
                        $userRoles,
                        $userId
                    ]);
                }

                unset($members[$index]);
            }

            DB::update("UPDATE `group_roles` SET `members`=? WHERE `id`=?", [
                json_encode($members),
                $groupRole->id
            ]);
        }

        DB::update("UPDATE `groups` SET `users`=? WHERE `id`=?", [
            json_encode($users),
            $id
        ]);

        return response([
            "success" => true,
            "users" => $this->getGroup($request, $id)->users
        ]);
    }

    public function updateUserRoles(Request $request, String $userId, String $groupId)
    {
        $roles = $request->input("roles");

        if (!isset($roles)) {
            return response([
                "success" => false,
                "message" => "EBADFIELDS"
            ], 400);
        }

        $userData = DB::select("SELECT * FROM `users` WHERE `id`=?", [$userId]);
        if (!array_key_exists(0, $userData)) {
            return response([
                "success" => false,
                "message" => "ENOUSER"
            ], 404);
        }

        $groupRoles = DB::select("SELECT * FROM `group_roles` WHERE `group_id`=?", [$groupId]);

        foreach ($groupRoles as $groupRole) {
            $members = json_decode($groupRole->members, true);

            if (in_array($groupRole->id, $roles) && !in_array($userId, $members)) {
                array_push($members, $userId);
            } else if (!in_array($groupRole->role_name, $roles) && in_array($userId, $members)) {
                unset($members[array_search($userId, $members)]);
            }

            DB::update("UPDATE `group_roles` SET `members`=? WHERE `group_id`=? AND `id`=?", [
                json_encode($members),
                $groupId,
                $groupRole->id
            ]);
        }

        $userRoles = json_decode($userData[0]->group_roles, true);

        if (count($roles) > 0) {
            $userRoles[$groupId] = $roles;
        } else {
            unset($userRoles[$groupId]);
        }

        $userRoles = json_encode($userRoles);
        if ($userRoles == "[]") {
            $userRoles = "{}";
        }

        DB::update("UPDATE `users` SET `group_roles`=? WHERE `id`=?", [
            $userRoles,
            $userId
        ]);

        return response([
            "success" => true
        ]);
    }

    public function createGroupPermission(Request $request, String $groupId)
    {
        $user = app('App\Http\Controllers\AuthController')->user($request);

        if (!$user->permissions["administrate:manage-permissions"]) {
            return response([
                "success" => false,
                "message" => "ENOPERMS"
            ], 401);
        }

        $action = $request->input("action");
        $subject = $request->input("subject");

        $existingNode = DB::select("SELECT * FROM `group_permissions` WHERE `group_id`=? AND `action`=? AND `subject`=?", [
            $groupId,
            $action,
            $subject
        ]);

        if (array_key_exists(0, $existingNode)) {
            return response([
                "success" => false,
                "message" => "EEXISTS"
            ], 409);
        }

        DB::insert("INSERT INTO `group_permissions` VALUES(?, ?, ?)", [
            $groupId,
            $action,
            $subject
        ]);

        return response([
            "success" => true,
            "root_permissions" => app('App\Http\Controllers\AuthController')->getPermissions(),
            "group_permissions" => $this->getGroup($request, $groupId)->permissions
        ]);
    }

    public function createGroupRole(Request $request, String $groupId)
    {
        $user = app('App\Http\Controllers\AuthController')->user($request);

        if (!$user->permissions["administrate:manage-group"]) {
            return response([
                "success" => false,
                "message" => "ENOPERMS"
            ], 401);
        }

        $roleName = $request->input("role_name");
        $position = $request->input("position");
        $permissions = $request->input("permissions");

        $existingRole = DB::select("SELECT * FROM `group_roles` WHERE `group_id`=? AND `role_name`=?", [
            $groupId,
            $roleName
        ]);

        if (array_key_exists(0, $existingRole)) {
            return response([
                "success" => false,
                "message" => "EEXSISTS"
            ]);
        }

        $permissions = json_encode($permissions);
        if ($permissions == "[]") {
            $permissions = "{}";
        }

        DB::insert("INSERT INTO `group_roles`(`group_id`, `role_name`, `position`, `permissions`) VALUES(?, ?, ?, ?)", [
            $groupId,
            $roleName,
            $position,
            $permissions
        ]);

        return response([
            "success" => true,
            "roles" => $this->getGroup($request, $groupId)->roles
        ]);
    }

    public function updateGroupRole(Request $request, String $groupId)
    {
        $user = app('App\Http\Controllers\AuthController')->user($request);

        if (!$user->permissions["administrate:manage-group"]) {
            return response([
                "success" => false,
                "message" => "ENOPERMS"
            ], 401);
        }

        $roleName = $request->input("role_name");
        $position = $request->input("position");
        $permissions = $request->input("permissions");

        $existingRole = DB::select("SELECT * FROM `group_roles` WHERE `group_id`=? AND `role_name`=?", [
            $groupId,
            $roleName
        ]);

        if (!array_key_exists(0, $existingRole)) {
            return response([
                "success" => false,
                "message" => "ENOROLE"
            ]);
        }

        $permissions = json_encode($permissions);
        if ($permissions == "[]") {
            $permissions = "{}";
        }

        DB::update("UPDATE `group_roles` SET `role_name`=?,`position`=?,`permissions`=? WHERE `group_id`=?;", [
            $roleName,
            $position,
            $permissions,
            $groupId,
        ]);

        return response([
            "success" => true,
            "roles" => $this->getGroup($request, $groupId)->roles
        ]);
    }

    public function deleteGroupRole(Request $request, String $groupId)
    {
        $user = app('App\Http\Controllers\AuthController')->user($request);

        if (!$user->permissions["administrate:manage-group"]) {
            return response([
                "success" => false,
                "message" => "ENOPERMS"
            ], 401);
        }

        $roleId = $request->input("role_id");

        $existingRole = DB::select("SELECT * FROM `group_roles` WHERE `group_id`=? AND `id`=?", [
            $groupId,
            $roleId
        ]);

        if (!array_key_exists(0, $existingRole)) {
            return response([
                "success" => false,
                "message" => "ENOTFOUND"
            ], 404);
        }

        $roleMembers = json_decode($existingRole[0]->members, true);
        foreach ($roleMembers as $userId) {
            $user = DB::select("SELECT * FROM `users` WHERE `id`=?", [$userId]);
            if (!array_key_exists(0, $user)) return;

            $groupRoles = json_decode($user[0]->group_roles, true);
            $userRoles = $groupRoles[$groupId];

            if (!in_array($roleId, $userRoles)) continue;
            unset($userRoles[array_search($roleId, $userRoles)]);

            $groupRoles[$groupId] = $userRoles;

            DB::update("UPDATE `users` SET `group_roles`=? WHERE `id`=?", [
                json_encode($groupRoles),
                $userId
            ]);
        }

        DB::delete("DELETE FROM `group_roles` WHERE `id`=? AND `group_id`=?", [
            $roleId,
            $groupId
        ]);

        $groupData = $this->getGroup($request, $groupId);

        return response([
            "success" => true,
            "roles" => $groupData->roles,
            "users" => $groupData->users
        ]);
    }

    public function deleteGroupPermission(Request $request, String $groupId)
    {
        $user = app('App\Http\Controllers\AuthController')->user($request);

        if (!$user->permissions["administrate:manage-permissions"]) {
            return response([
                "success" => false,
                "message" => "ENOPERMS"
            ], 401);
        }

        $action = $request->input("action");
        $subject = $request->input("subject");

        $existingNode = DB::select("SELECT * FROM `group_permissions` WHERE `group_id`=? AND `action`=? AND `subject`=?", [
            $groupId,
            $action,
            $subject
        ]);

        if (!array_key_exists(0, $existingNode)) {
            return response([
                "success" => false,
                "message" => "ENOTFOUND"
            ], 404);
        }

        DB::delete("DELETE FROM `group_permissions` WHERE `group_id`=? AND `action`=? AND `subject`=?", [
            $groupId,
            $action,
            $subject
        ]);

        return response([
            "success" => true,
            "root_permissions" => app('App\Http\Controllers\AuthController')->getPermissions(),
            "group_permissions" => $this->getGroup($request, $groupId)->permissions
        ]);
    }
}
