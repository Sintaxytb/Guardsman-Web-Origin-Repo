<?php

namespace App\Console;

use Illuminate\Support\Facades\DB;
use App\Console\StatisticsManager;

class ActiveServerCount
{
    public function __invoke()
    {
        $activeServerCount = "Unknown";
        $activePlayerCount = "Unknown";
        $queryCount = DB::select("SELECT COUNT(*) AS `count` FROM `servers`;");

        if (array_key_exists(0, $queryCount))
        {
            $activeServerCount = $queryCount[0]->count;
        }

        $playerCount = DB::select("SELECT SUM(player_count) AS `player_count` FROM `servers`;");

        if (array_key_exists(0, $playerCount))
        {
            $activePlayerCount = $playerCount[0]->player_count;
            if ($activePlayerCount == null)
            {
                $activePlayerCount = 0;
            }
        }

        StatisticsManager::shiftDataByIndex($activeServerCount, "servers");
        StatisticsManager::shiftDataByIndex($activePlayerCount, "players");
    }
}
