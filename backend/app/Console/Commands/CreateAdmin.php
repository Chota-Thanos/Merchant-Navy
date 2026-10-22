<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;

/** Creates (or resets the password of) an admin who can log in to admin.mymerchantnavy.com. */
class CreateAdmin extends Command
{
    protected $signature = 'lms:create-admin {email : Login email} {--name=Admin : Display name} {--password= : Leave empty to be asked}';

    protected $description = 'Create an admin user, or reset the password of an existing one';

    public function handle(): int
    {
        $email = strtolower($this->argument('email'));
        $password = $this->option('password') ?: $this->secret('Password (at least 12 characters)');

        if (strlen((string) $password) < 12) {
            $this->error('The password must be at least 12 characters.');

            return self::FAILURE;
        }

        $admin = Admin::updateOrCreate(['email' => $email], ['name' => $this->option('name'), 'password' => $password]);
        $this->info($admin->wasRecentlyCreated ? "Admin {$email} created." : "Password of {$email} updated.");

        return self::SUCCESS;
    }
}
