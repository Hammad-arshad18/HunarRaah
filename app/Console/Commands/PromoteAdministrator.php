<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PromoteAdministrator extends Command
{
    protected $signature = 'platform:promote-admin {email} {--reason=} {--confirm-owner : Confirm this is an owner-authorized CLI operation}';

    protected $description = 'Promote an existing owner-approved account; preserve password and require administrator MFA';

    public function handle(): int
    {
        if (! $this->option('confirm-owner') || ! trim((string) $this->option('reason'))) {
            $this->error('Supply --confirm-owner and an audit --reason for this controlled CLI operation.');

            return self::FAILURE;
        }
        $user = User::where('email', strtolower(trim($this->argument('email'))))->first();
        if (! $user) {
            $this->error('Account not found. Create the initial administrator interactively with php artisan platform:admin.');

            return self::FAILURE;
        }
        if ($user->suspended_at) {
            $this->error('Suspended accounts cannot be promoted.');

            return self::FAILURE;
        }
        DB::transaction(function () use ($user) {
            $user->forceFill(['role' => 'admin', 'email_verified_at' => $user->email_verified_at ?? now(), 'remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('audit_logs')->insert(['actor_id' => $user->id, 'action' => 'admin.promoted', 'subject_type' => 'user', 'subject_id' => $user->id, 'reason' => (string) $this->option('reason'), 'created_at' => now()]);
        });
        $this->info('Administrator access granted. Sign in with the existing password and configure the authenticator under Account / Security.');

        return self::SUCCESS;
    }
}
