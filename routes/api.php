<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StoredUsersController;
use App\Http\Controllers\PunishmentsController;
use App\Http\Controllers\PanelAdministrationController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\GameAPIController;
use App\Http\Controllers\DiscordAPIController;
use App\Http\Controllers\GroupsController;
use App\Http\Controllers\PublicAPIController;
use App\Http\Middleware\EnsureDiscordAPIHasAuthorization;
use App\Http\Middleware\EnsureGameAPIHasAuthorization;
use App\Http\Middleware\EnsurePublicAPIHasAuthorization;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// BEGIN AUTH ROUTES \\
Route::post('user/login/guardsman', [AuthController::class, 'login']);

Route::middleware(['web'])->group(function () {
    Route::get('user/login/roblox', [AuthController::class, 'loginRoblox']);
    Route::get('user/login/discord', [AuthController::class, 'loginDiscord']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::put('user/create-password', [AuthController::class, 'createPassword']);
    Route::get('user', [AuthController::class, 'user']);
    Route::delete('user/logout', [AuthController::class, 'logout']);
    Route::patch('user/change-password', [AuthController::class, 'changePassword']);
    Route::get('user/api-keys', [AuthController::class, 'apiKeys']);
    Route::post('user/api-key/{name}', [AuthController::class, 'createAPIKey']);
    Route::delete('user/api-key/{id}', [AuthController::class, 'deleteAPIKey']);
    Route::get('user/groups', [AuthController::class, 'getUserGroups']);
});

// END AUTH ROUTES \\

// BEGIN DISCORD ROUTES \\
Route::middleware([EnsureDiscordAPIHasAuthorization::class])->group(function () {
    Route::post('discord/bot/startup', [DiscordAPIController::class, 'onBotStartup']);
    Route::patch('discord/bot/checkin', [DiscordAPIController::class, 'onBotPing']);
    Route::get('discord/search/{query}', [DiscordAPIController::class, 'searchUser']);
    Route::get('discord/user/by-username/{username}', [DiscordAPIController::class, 'getUserByUsername']);
    Route::get('discord/user/by-discord/{userId}', [DiscordAPIController::class, 'getUserByDiscordId']);
    Route::get('discord/user/{id}', [DiscordAPIController::class, 'getUserByGuardsmanId']);
    Route::post('discord/user/{id}/punishment', [DiscordAPIController::class, 'punishUser']);
    Route::get('discord/{guildId}/settings', [DiscordAPIController::class, 'getGuildConfiguration']);
    Route::put('discord/{guildId}/settings', [DiscordAPIController::class, 'setGuildConfiguration']);
});
// END DISCORD ROUTES \\

// BEGIN GAME ROUTES \\
Route::middleware([EnsureGameAPIHasAuthorization::class])->group(function () {
    Route::post('game/{id}', [GameAPIController::class, 'serverStarted']);
    Route::get('game/{id}/player/{identifier}', [GameAPIController::class, 'getPlayer']);
    Route::post('game/{id}/player/{identifier}', [GameAPIController::class, 'createPlayer']);
    Route::post('game/{id}/player/{identifier}/punishment', [GameAPIController::class, 'punishUser']);
    Route::post('game/{id}/player/{identifier}/hash', [GameAPIController::class, 'submitHash']);
    Route::patch('game/{id}', [GameAPIController::class, 'updateServer']);
    Route::delete('game/{id}', [GameAPIController::class, 'deleteServer']);
    Route::post('game/{id}/exploit-log', [GameAPIController::class, 'exploitLog']);
});
// END GAME ROUTES \\

// BEGIN PUBLIC API ROUTES \\
Route::middleware([EnsurePublicAPIHasAuthorization::class])->group(function () {
    Route::get('users/{identifier}', [PublicAPIController::class, 'getUser']);
});

Route::get('punishments/types', [PunishmentsController::class, 'getPunishmentTypes']);
// END PUBLIC API ROUTES \\

// INTERNAL API CALLS
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('servers', [GameAPIController::class, 'getServers']);
    Route::post('game/{id}/message/{topic}', [GameAPIController::class, 'messageServer']);
    Route::post('game/{id}/remote-execute', [GameAPIController::class, 'remoteExecute']);
    Route::post('game/{id}/close-server', [GameAPIController::class, 'closeServer']);

    Route::get('audits', [AuditLogController::class, 'getAuditLogs']);
    Route::get('audits/by-moderator/{moderator}', [AuditLogController::class, 'getAuditsByModerator']);

    Route::get('permissions', [AuthController::class, 'getPermissions']);
    Route::post('permission', [AuthController::class, 'createPermission']);
    Route::delete('permission', [AuthController::class, 'deletePermission']);
    Route::get('roles', [AuthController::class, 'getRoles']);
    Route::put('roles/{role}', [AuthController::class, 'updateRole']);
    Route::delete('roles/{role}', [AuthController::class, 'deleteRole']);

    Route::get('thumbnail/{userId}', [StoredUsersController::class, 'getThumbnail']);
    Route::get('user/{identifier}', [StoredUsersController::class, 'getUser']);
    Route::get('user/{id}/accounts', [StoredUsersController::class, 'getAccounts']);
    Route::get('search/{identifier}', [StoredUsersController::class, 'searchUser']);

    Route::patch('user/{id}', [StoredUsersController::class, 'updateUser']);
    Route::post('remote-execute/{jobId}', [GameAPIController::class, 'remoteExecute']);

    Route::get('punishments', [PunishmentsController::class, 'getPunishments']);
    Route::get('punishments/by-moderator/{moderator}', [PunishmentsController::class, 'getPunishmentsByModerator']);

    Route::post('user/{user}/punishment', [PunishmentsController::class, 'punishUserByGuardsmanId']);
    Route::patch('user/{username}/punishment/{punishmentId}/reinstate', [PunishmentsController::class, 'reinstatePunishment']);
    Route::get('user/{user}/punishment/{id}/evidence', [PunishmentsController::class, 'getPunishmentEvidence']);
    Route::patch('user/{user}/punishment/{id}/evidence', [PunishmentsController::class, 'updateEvidence']);
    Route::delete('user/{username}/punishment/{punishmentId}/disable', [PunishmentsController::class, 'disablePunishment']);
    Route::delete('user/{username}/punishment/{punishmentId}/full', [PunishmentsController::class, 'deletePunishment']);

    Route::get('administrators', [PanelAdministrationController::class, 'getAdministrators']);

    Route::get('shout', [PanelAdministrationController::class, 'getShout']);
    Route::put('shout', [PanelAdministrationController::class, 'updateShout']);

    Route::delete('delete-user/{user}', [AuthController::class, 'deleteUser']);

    // BEGIN GROUPS API ROUTES \\
    Route::get('group/{id}', [GroupsController::class, 'getGroup']);
    Route::get('groups', [GroupsController::class, 'getGroups']);
    Route::post('group/create', [GroupsController::class, 'createGroup']);
    Route::patch('group/{id}/users', [GroupsController::class, 'updateGroupUsers']);
    Route::patch('user/{userId}/group/{groupId}/roles', [GroupsController::class, 'updateUserRoles']);
    Route::delete('group/{id}', [GroupsController::class, 'deleteGroup']);

    Route::post('group/{id}/role', [GroupsController::class, 'createGroupRole']);
    Route::post('group/{id}/permission', [GroupsController::class, 'createGroupPermission']);

    Route::patch('group/{id}/role', [GroupsController::class, 'updateGroupRole']);

    Route::delete('group/{id}/role', [GroupsController::class, 'deleteGroupRole']);
    Route::delete('group/{id}/permission', [GroupsController::class, 'deleteGroupPermission']);

    // END GROUPS API ROUTES \\

});
