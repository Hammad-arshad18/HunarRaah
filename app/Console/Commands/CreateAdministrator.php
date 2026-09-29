<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateAdministrator extends Command
{
    protected $signature = 'platform:admin';

    protected $description = 'Interactively create the initial administrator; MFA must be configured on first login';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Run this command interactively.');

            return self::FAILURE;
        }
        $data = ['name' => $this->ask('Full name'), 'email' => strtolower(trim($this->ask('Email'))), 'password' => $this->secret('Password (12–128 characters)')];
        $validator = Validator::make($data, ['name' => 'required|string|max:255', 'email' => 'required|email|unique:users', 'password' => 'required|string|min:12|max:128']);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }
        DB::transaction(function () use ($data) {
            $user = User::create($data);
            $user->forceFill(['role' => 'admin', 'email_verified_at' => now()])->save();
            DB::table('audit_logs')->insert(['actor_id' => $user->id, 'action' => 'admin.bootstrapped', 'subject_type' => 'user', 'subject_id' => $user->id, 'reason' => 'Interactive owner-controlled CLI bootstrap', 'created_at' => now()]);
        });
        $this->info('Administrator created. Sign in, confirm your password and enable TOTP under Settings / Security.');

        return self::SUCCESS;
    }
}
