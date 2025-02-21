<?php

namespace App\Console;

use Illuminate\Support\Facades\DB;
use App\Console\StatisticsManager;

class VerifiedUserCount
{
    public function __invoke()
    {
        $verifiedUserCount = "Unknown";
        $queryCount = DB::select("SELECT COUNT(*) AS `count` FROM `users`;");

        if (array_key_exists(0, $queryCount)) {
            $verifiedUserCount = $queryCount[0]->count;
        }

        var_dump($verifiedUserCount);

        StatisticsManager::shiftDataByIndex($verifiedUserCount, "users");
    }
}
