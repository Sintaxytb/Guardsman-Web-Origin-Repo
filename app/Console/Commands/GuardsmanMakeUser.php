<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class GuardsmanMakeUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'g:user:make';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prompts to create a user in the Guardsman database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $username = $this->ask("Enter a username for the account");

        if ($username == NULL) {
            $this->error("You must provide a username!");
            return $this->handle();
        }

        $robloxId = $this->ask("Enter a Roblox ID for the account");

        if ($robloxId == NULL) {
            $this->error("You must provide a Roblox ID!");
            return $this->handle();
        }

        $discordId = $this->ask("Enter a Discord ID for the account");
        $password = $this->secret("Enter a password for the account");

        $roles = DB::select("SELECT * FROM `roles`");
        $roleNames = array();

        foreach ($roles as $role) {
            array_push($roleNames, $role->name);
        }

        $roles = $this->choice(
            "Select roles to add to the user (comma separated)",
            $roleNames,
            null,
            null,
            true
        );

        $this->info("Creating user...");

        $userData = DB::insert("INSERT INTO `users`(`username`, `roblox_id`, `discord_id`, `password`, `roles`) VALUES(?,?,?,?,?)", [
            $username,
            $robloxId,
            $discordId,
            Hash::make($password),
            json_encode($roles)
        ]);

        $this->info("Successfully created a new user!");
    }
}
