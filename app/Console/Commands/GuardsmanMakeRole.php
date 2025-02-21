<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class GuardsmanMakeRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'g:role:make';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prompts to create a role in the Guardsman database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->ask("Enter a name for the role");

        if ($name == NULL) {
            $this->error("You must provide a role name!");
            return $this->handle();
        }

        $position = $this->ask("Enter a position for the role (lower = less privileged)");

        if ($position == NULL) {
            $this->error("You must provide a role position!");
            return $this->handle();
        }

        $permissions = DB::select("SELECT * FROM `permissions`");
        $permissionNodes = array();

        foreach ($permissions as $permission) {
            array_push($permissionNodes, $permission->action . ":" . $permission->subject);
        }

        $nodes = $this->choice(
            "Select roles to add to the user (comma separated)",
            $permissionNodes,
            null,
            null,
            true
        );

        $selectedNodes = array();

        foreach ($permissions as $permission) {
            $node = $permission->action . ":" . $permission->subject;

            $selectedNodes[$node] = in_array($node, $nodes);
        }

        $this->info("Creating role...");

        DB::insert("INSERT INTO `roles`(`name`, `position`, `permissions`) VALUES(?,?,?)", [
            $name,
            $position,
            json_encode($selectedNodes)
        ]);

        $this->info("Successfully created a new role!");
    }
}
