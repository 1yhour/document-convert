<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class MakeAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:make-admin {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign the admin role to an existing user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email '{$email}' not found.");
            return self::FAILURE;
        }

        // Ensure the admin role exists
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $user->syncRoles(['admin']);

        $this->info("User '{$email}' has been successfully assigned the 'admin' role.");

        return self::SUCCESS;
    }
}
