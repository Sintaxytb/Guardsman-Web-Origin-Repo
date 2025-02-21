<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    public static function submitLog($user, $type, $action) {
        $type = str_replace(" ", "", $type);
        DB::insert("INSERT INTO `audit_logs` (`type`, `user`, `action`) VALUES(?, ?, ?);", [strtoupper($type), $user, $action]);
    }

    public function getAuditLogs(Request $request) {
        $auditLogs = DB::select("SELECT * FROM `audit_logs` ORDER BY `id` DESC;");

        $userIds = array();

        foreach ($auditLogs as $auditLog) {
            $user = null;

            if (isset($userIds[$auditLog->user])) {
                $user = $userIds[$auditLog->user];
            } else {
                $foundUser = DB::select("SELECT * FROM `users` WHERE `id`=?", [$auditLog->user]);
                if (isset($foundUser[0])) {
                    $user = $foundUser[0]->username;
                    $userIds[$auditLog->user] = $foundUser[0]->username;
                } else {
                    $user = "Unknown.";
                }
            }

            $auditLog->user = $user;

            $words = explode(" ", $auditLog->action);

            foreach ($words as $index=>$word) {
                $replaceComponent = explode("::", $word);
                
                if (count($replaceComponent) > 1) {
                    $userId = $replaceComponent[1];
                    $user = null;

                    if (isset($userIds[$userId])) {
                        $user = $userIds[$userId];
                    } else {
                        $foundUser = DB::select("SELECT * FROM `users` WHERE `id`=?", [$userId]);
                        if (isset($foundUser[0])) {
                            $user = $foundUser[0]->username;
                            $userIds[$userId] = $foundUser[0]->username;
                        } else {
                            $user = "Unknown.";
                        }
                    }

                    $words[$index] = $user;
                }
            }

            $auditLog->action = implode(" ", $words);
        }

        return $auditLogs;
    }

    public function getAuditsByModerator(Request $request, String $moderator) {
        $auditLogs = DB::select("SELECT * FROM `audit_logs` WHERE `user`=? ORDER BY `id` DESC", [$moderator]);

        $userIds = array();

        foreach ($auditLogs as $auditLog) {
            $user = null;

            if (isset($userIds[$auditLog->user])) {
                $user = $userIds[$auditLog->user];
            } else {
                $foundUser = DB::select("SELECT * FROM `users` WHERE `id`=?", [$auditLog->user]);
                if (isset($foundUser[0])) {
                    $user = $foundUser[0]->username;
                    $userIds[$auditLog->user] = $foundUser[0]->username;
                } else {
                    $user = "Unknown.";
                }
            }

            $auditLog->user = $user;

            $words = explode(" ", $auditLog->action);

            foreach ($words as $index=>$word) {
                $replaceComponent = explode("::", $word);
                
                if (count($replaceComponent) > 1) {
                    $userId = $replaceComponent[1];
                    $user = null;

                    if (isset($userIds[$userId])) {
                        $user = $userIds[$userId];
                    } else {
                        $foundUser = DB::select("SELECT * FROM `users` WHERE `id`=?", [$userId]);
                        if (isset($foundUser[0])) {
                            $user = $foundUser[0]->username;
                            $userIds[$userId] = $foundUser[0]->username;
                        } else {
                            $user = "Unknown.";
                        }
                    }

                    $words[$index] = $user;
                }
            }

            $auditLog->action = implode(" ", $words);
        }

        return $auditLogs;
    }
}
