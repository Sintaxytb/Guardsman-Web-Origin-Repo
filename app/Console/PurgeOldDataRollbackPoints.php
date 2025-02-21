<?php

namespace App\Console;

use Illuminate\Support\Facades\DB;

class PurgeOldDataRollbackPoints
{
    public function __invoke()
    {
        DB::delete("DELETE FROM `data_rollback_points` WHERE `created_at` < (NOW() - INTERVAL 7 DAY);");

        DB::insert("INSERT INTO `data_rollback_points` (`user_id`, `game_name`, `game_data`) SELECT  `user_id`, `game_name`, `game_data` FROM `game_data` WHERE `game_data`.`updated_at` > (NOW() - INTERVAL 1 DAY);");
    }
}
