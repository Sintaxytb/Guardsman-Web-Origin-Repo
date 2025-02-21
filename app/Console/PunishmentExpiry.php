<?php

namespace App\Console;

use Illuminate\Support\Facades\DB;

class PunishmentExpiry
{
    public function __invoke()
    {
        DB::update("UPDATE `punishments` SET `active`=0 WHERE `expires` IS NOT NULL AND `active`=1 AND `expires` < UNIX_TIMESTAMP();");
    }
}
