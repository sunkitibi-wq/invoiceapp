<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeUserAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:make-admin {email : The email address of the user} {--revoke : Remove superuser access instead}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant a user superuser access, which bypasses every permission check';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error('No user found with that email address.');

            return self::FAILURE;
        }

        // is_admin is deliberately absent from the fillable attributes, so it
        // has to be assigned explicitly.
        $user->forceFill(['is_admin' => ! $this->option('revoke')])->save();

        $this->info($user->isAdmin()
            ? "{$user->name} is now a superuser."
            : "{$user->name} is no longer a superuser.");

        return self::SUCCESS;
    }
}
