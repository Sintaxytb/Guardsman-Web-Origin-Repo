<?php

namespace App\Console;

use Illuminate\Support\Facades\DB;

class PurgeOldServers
{
    public function __invoke()
    {
        DB::delete("DELETE FROM `servers` WHERE `last_ping` < (NOW() - INTERVAL 5 MINUTE)");
    }
}
