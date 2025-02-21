<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AuditLogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
  private function getGUID()
  {
    mt_srand((float)microtime() * 10000); //optional for php 4.2.0 and up.
    $charid = strtoupper(md5(uniqid(rand(), true)));
    $hyphen = chr(45); // "-"
    $uuid = substr($charid, 0, 64);
    return $uuid;
  }

  public function user(Request $request)
  {
    $user = $request->user();

    return app('App\Http\Controllers\StoredUsersController')->getUser($request, $user->id);
  }

  public function createPassword(Request $request)
  {
    $authUser = $request->user();
    $user = DB::select("SELECT * FROM `users` WHERE `id`=?", [$authUser->id])[0];
    $password = $request->input("password");

    if (empty($password)) {
      return response([
        "success" => false,
        "status" => "ENOPASS"
      ], 400);
    }

    if ($user->password != null) {
      return response([
        "success" => false,
        "status" => "EPASSEXISTS"
      ], 401);
    }

    $hashedPassword = Hash::make($password);
    DB::update("UPDATE `users` SET `password`=? WHERE `id`=?", [$hashedPassword, $user->id]);

    return response([
      "success" => true
    ]);
  }

  public function loginRoblox(Request $request)
  {
    $code = $request->input("code");

    $curlHandle = curl_init();

    curl_setopt($curlHandle, CURLOPT_URL, "https://apis.roblox.com/oauth/v1/token");
    curl_setopt($curlHandle, CURLOPT_POST, 1);
    curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded"));
    curl_setopt($curlHandle, CURLOPT_POSTFIELDS, http_build_query(array(
      "client_id" => env("VITE_ROBLOX_OAUTH_CLIENT_ID"),
      "client_secret" => env("ROBLOX_OAUTH_CLIENT_SECRET"),
      "grant_type" => "authorization_code",
      "code" => $code,
    )));

    curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($curlHandle);

    curl_close($curlHandle);

    $decoded = json_decode($response);
    if (!isset($decoded->access_token)) {
      return redirect("/login?status=EBADCODE");
    }

    $curlHandle = curl_init();

    curl_setopt($curlHandle, CURLOPT_URL, "https://apis.roblox.com/oauth/v1/token/resources");
    curl_setopt($curlHandle, CURLOPT_POST, 1);
    curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded"));
    curl_setopt($curlHandle, CURLOPT_POSTFIELDS, http_build_query(array(
      "client_id" => env("VITE_ROBLOX_OAUTH_CLIENT_ID"),
      "client_secret" => env("ROBLOX_OAUTH_CLIENT_SECRET"),
      "token" => $decoded->access_token
    )));

    curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($curlHandle);

    curl_close($curlHandle);

    $userData = json_decode($response);
    $resourceInfo = $userData->resource_infos[0];
    $owner = $resourceInfo->owner;
    $ownerId = $owner->id;

    $user = DB::select("SELECT * FROM `users` WHERE `roblox_id`=?", [$ownerId]);

    if (!isset($user[0])) {
      return redirect("/login?status=ENOUSER");
    }

    if ($user[0]->roles == '["Player"]') {
      return redirect("/login?status=ENOACCESS");
    }

    if (!Auth::loginUsingId($user[0]->id)) {
      return redirect("/login?status=ENOUSER");
    }

    /** @var \App\Models\User $authUser **/ $authUser = Auth::user();

    if ($authUser == null) {
      return response([
        "success" => false,
        "message" => "ESERVERFAULT"
      ], 500);
    }

    $token = $authUser->createToken("token")->plainTextToken;
    $cookie = cookie("jwt", $token, 60 * 24); // 1 day by default

    if ($authUser->password == null) {
      return redirect("/create-password")->withCookie($cookie);
    }

    return redirect("/dashboard?status=LOGIN")->withCookie($cookie);
  }

  public function loginDiscord(Request $request)
  {
    $code = $request->input("code");
    $isElectron = $request->input("electron");
    $curlHandle = curl_init();
    $appUrl = env('APP_URL');
    $electronPort = env('VITE_ELECTRON_AUTH_PORT');

    curl_setopt($curlHandle, CURLOPT_URL, "https://discord.com/api/v10/oauth2/token");
    curl_setopt($curlHandle, CURLOPT_POST, 1);
    curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded"));
    curl_setopt($curlHandle, CURLOPT_POSTFIELDS, http_build_query(array(
      "client_id" => env("VITE_DISCORD_OAUTH_CLIENT_ID"),
      "client_secret" => env("DISCORD_OAUTH_CLIENT_SECRET"),
      "grant_type" => "authorization_code",
      "code" => $code,
      "redirect_uri" => ($isElectron == true) ? "http://127.0.0.1:" . $electronPort . '/auth-discord' : "$appUrl/api/user/login/discord"
    )));

    curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($curlHandle);

    curl_close($curlHandle);

    $decoded = json_decode($response);
    if (!isset($decoded->access_token)) {
      return redirect("/login?status=EBADCODE&decoded=$response&hi=hi");

      return;
    }

    $tokenType = $decoded->token_type;
    $accessToken = $decoded->access_token;

    $curlHandle = curl_init();

    curl_setopt($curlHandle, CURLOPT_URL, "https://discord.com/api/users/@me");
    curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array(
      "Content-Type: application/x-www-form-urlencoded",
      "Authorization: $tokenType $accessToken"
    ));

    curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($curlHandle);

    curl_close($curlHandle);
    $userData = json_decode($response);
    $userId = $userData->id;

    $user = DB::select("SELECT * FROM `users` WHERE `discord_id`=?", [$userId]);

    if (!isset($user[0])) {
      return redirect("/login?status=ENOUSER");
    }

    if ($user[0]->roles == '["Player"]') {
      return redirect("/login?status=ENOACCESS");
    }

    if (!Auth::loginUsingId($user[0]->id)) {
      return redirect("/login?status=ENOUSER");
    }

    /** @var \App\Models\User $authUser **/ $authUser = Auth::user();

    if ($authUser == null) {
      return response([
        "success" => false,
        "message" => "ESERVERFAULT"
      ], 500);
    }

    $token = $authUser->createToken("token")->plainTextToken;
    $cookie = cookie("jwt", $token, 60 * 24); // 1 day by default

    if ($authUser->password == null) {
      return redirect("/create-password")->withCookie($cookie);
    }

    return redirect("/dashboard?status=LOGIN")->withCookie($cookie);
  }

  public function login(Request $request)
  {
    if (!Auth::attempt($request->only("username", "password"))) {
      return response([
        "success" => false,
        "status" => "ENOCREDS",
      ], Response::HTTP_UNAUTHORIZED);
    }

    /** @var \App\Models\User $authUser **/ $authUser = Auth::user();

    if ($authUser == null) {
      return response([
        "success" => false,
        "message" => "ESERVERFAULT"
      ], 500);
    }

    $token = $authUser->createToken("token")->plainTextToken;
    $cookie = cookie("jwt", $token, 60 * 24); // 1 day by default

    return response([
      "success" => true,
    ])->withCookie($cookie);
  }

  public function changePassword(Request $request)
  {
    $user = $request->user();
    $currentPassword = $request->input("current_password");
    $newPassword = $request->input("new_password");

    if (!isset($currentPassword) || !isset($newPassword)) {
      return response([
        "success" => false
      ], 400);
    }

    $storedUser = DB::select("select * from users where id=?", [$user->id])[0];

    if (!Hash::check($currentPassword, $storedUser->password)) {
      return response([
        "success" => false,
        "status" => "ENOCREDS"
      ], 401);
    }

    DB::update("update users set password=? where id=?", [Hash::make($newPassword), $user->id]);

    $jwtCookie = Cookie::forget("jwt");

    return response([
      "success" => true
    ])->withCookie($jwtCookie);
  }

  public function logout(Request $request)
  {
    $user = $request->user();
    $jwtCookie = Cookie::forget("jwt");

    Session::flush();
    $user->tokens()->delete();

    return response([
      "success" => true,
    ])->withCookie($jwtCookie);
  }

  public function deleteUser(Request $request, String $username)
  {
    $user = $request->user();
    $userName = $user->username;

    $existingUser = DB::select("SELECT * FROM `users` WHERE `username`=?", [
      $username
    ]);

    if (!isset($existingUser[0])) {
      return response([
        "success" => false,
        "status" => "ENOUSER"
      ], 400);
    }

    AuditLogController::submitLog($userName, "USER_DELETE", "$userName deleted user $username.");

    DB::delete("delete from users where username=?", [
      $username
    ]);

    return response([
      "success" => true
    ]);
  }

  public function getRoles(Request $request)
  {
    $roles = DB::select("SELECT * FROM `roles` ORDER BY `position` ASC");

    return $roles;
  }

  public function updateRole(Request $request, String $role)
  {
    $user = $request->user();
    $userName = $user->username;
    $roleName = urldecode($role);
    $existingRole = DB::select("SELECT * FROM `roles` WHERE `name`=?", [$roleName]);
    $rolePosition = null;
    $permissions = $request->input("permissions");
    $isCreate = "UPDATE";

    if (isset($existingRole[0])) {
      $rolePosition = $existingRole[0]->position;
    } else {
      $rolePosition = $request->input("position");
      $isCreate = "CREATE";
    }

    if (is_null($rolePosition)) {
      return response([
        "success" => false,
        "status" => "ENOLEVEL"
      ], 400);
    }

    if ($isCreate == "CREATE") {
      DB::insert("INSERT INTO `roles`(`name`, `position`, `permissions`) VALUES(?, ?, ?)", [
        $roleName,
        $rolePosition,
        $permissions
      ]);
    } else {
      DB::update("UPDATE `roles` SET `name`=?, `position`=?, `permissions`=? WHERE `name`=?", [
        $roleName,
        $rolePosition,
        $permissions,
        $roleName
      ]);
    }

    AuditLogController::submitLog($user->id, "ROLE_$isCreate", "$userName " . strtolower($isCreate) . " role $roleName.");

    return response([
      "success" => true,
    ]);
  }

  public function deleteRole(Request $request, String $role)
  {
    $user = $request->user();
    $userName = $user->username;
    $roleName = urldecode($role);
    $permissions = array();
    $existingRole = DB::select("SELECT * FROM `roles` WHERE `name`=?", [$roleName]);

    if (!isset($existingRole[0])) {
      return response([
        "success" => false,
        "status" => "ENOROLE"
      ], 404);
    }

    foreach (json_decode($user->roles) as $name) {
      $userRole = DB::select("SELECT * FROM `roles` WHERE `name`=?", [$name])[0];

      $rolePermissions = json_decode($userRole->permissions);

      foreach ($rolePermissions as $permission) {
        if (!in_array($permission, $permissions)) {
          array_push($permissions, $permission);
        }
      }
    }

    $hasPermission = array_search("administrate:manage-panel", $permissions);

    if (!$hasPermission) {
      return response([
        "success" => false,
        "status" => "ENOPERMS"
      ], 401);
    }

    DB::delete("DELETE FROM `roles` WHERE `name`=?", [$roleName]);

    AuditLogController::submitLog($userName, "ROLE_DELETE", "$userName deleted role $roleName.");

    return response([
      "success" => true,
    ]);
  }

  public function apiKeys(Request $request)
  {
    $user = $request->user();
    $keys = DB::select("SELECT * FROM `api_keys` WHERE `user_id`=?", [
      $user->id
    ]);

    return $keys;
  }

  public function createAPIKey(Request $request, String $keyName)
  {
    // validate scopes
    $user = $this->user($request);
    $scopes = $request->input("scopes");

    $scopeMap = [
      "users.read" => "moderate:search",
      "users.write" => "moderate:moderate",
      "servers.read" => "manage:servers",
      "servers.execute" => "development:remote-execute"
    ];

    foreach ($scopeMap as $index => $value) {
      if (!in_array($index, $scopes)) continue;
      if (!in_array($value, $user->permissions)) {
        return response([
          "success" => false,
          "message" => "ENOPERMS"
        ], 401);
      }
    }

    $key = $this->getGUID();

    $existingKey = DB::select("SELECT * FROM `api_keys` WHERE `user_id`=? AND `name`=?", [
      $user->id,
      $keyName
    ]);

    if (array_key_exists(0, $existingKey)) {
      return response([
        "success" => false,
        "message" => "EEXISTS"
      ], 409);
    }

    DB::insert("INSERT INTO `api_keys`(`user_id`, `name`, `key`, `scopes`) VALUES(?, ?, ?, ?)", [
      $user->id,
      $keyName,
      $key,
      json_encode(array_keys($scopes))
    ]);

    $keys = $this->apiKeys($request);

    return response([
      "success" => true,
      "key" => $key,
      "keys" => $keys
    ], 200);
  }

  public function deleteAPIKey(Request $request, String $id)
  {
    $user = $this->user($request);
    $existingKey = DB::select("SELECT * FROM `api_keys` WHERE `user_id`=? AND `id`=?", [
      $user->id,
      $id
    ]);

    if (!array_key_exists(0, $existingKey)) {
      return response([
        "success" => false,
        "message" => "ENOTFOUND"
      ], 404);
    }

    DB::delete("DELETE FROM `api_keys` WHERE `id`=?", [$id]);

    $keys = $this->apiKeys($request);

    return response([
      "success" => true,
      "keys" => $keys
    ], 200);
  }

  public function getPermissions()
  {
    $permissions = DB::select("SELECT * FROM `permissions`");
    return $permissions;
  }

  public function createPermission(Request $request)
  {
    $user = $this->user($request);

    if (!$user->permissions["administrate:manage-permissions"]) {
      return response([
        "success" => false,
        "message" => "ENOPERMS"
      ], 401);
    }

    $action = $request->input("action");
    $subject = $request->input("subject");

    $existingNode = DB::select("SELECT * FROM `permissions` WHERE `action`=? AND `subject`=?", [
      $action,
      $subject
    ]);

    if (array_key_exists(0, $existingNode)) {
      return response([
        "success" => false,
        "message" => "EEXISTS"
      ], 409);
    }

    DB::insert("INSERT INTO `permissions` VALUES(?, ?)", [
      $action,
      $subject
    ]);

    return response([
      "success" => true,
      "permissions" => $this->getPermissions()
    ]);
  }

  public function deletePermission(Request $request)
  {
    $user = $this->user($request);

    if (!$user->permissions["administrate:manage-permissions"]) {
      return response([
        "success" => false,
        "message" => "ENOPERMS"
      ], 401);
    }

    $action = $request->input("action");
    $subject = $request->input("subject");

    $existingNode = DB::select("SELECT * FROM `permissions` WHERE `action`=? AND `subject`=?", [
      $action,
      $subject
    ]);

    if (!array_key_exists(0, $existingNode)) {
      return response([
        "success" => false,
        "message" => "ENOTFOUND"
      ], 409);
    }

    DB::delete("DELETE FROM `permissions` WHERE `action`=? AND `subject`=?", [
      $action,
      $subject
    ]);

    return response([
      "success" => true,
      "permissions" => $this->getPermissions()
    ]);
  }

  public function getUserGroups(Request $request)
  {
    $user = $this->user($request);
    $groups = app('App\Http\Controllers\GroupsController')->getGroups($request);

    $userGroups = array();
    $groupRoles = $user->group_roles;

    foreach ($groups as $group) {
      if (!array_key_exists($group->id, $groupRoles) && !$user->permissions["override:groups"]) continue;
      array_push($userGroups, $group);
    }

    return $userGroups;
  }
}
