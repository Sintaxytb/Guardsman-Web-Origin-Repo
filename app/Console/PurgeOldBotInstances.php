<?php

namespace App\Console;

use Illuminate\Support\Facades\DB;

class PurgeOldBotInstances
{
    public function __invoke()
    {
        DB::delete("DELETE FROM `discord_instances` WHERE `last_ping` < (NOW() - INTERVAL 2 MINUTE)");
    }
}
