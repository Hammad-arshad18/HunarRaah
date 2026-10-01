<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Owner-requested local bootstrap; production administrators use the controlled CLI. */
class AdministratorSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('AdministratorSeeder is restricted to local/testing. Use platform:admin in production.');
        }
        $email = 'admin@mirzalearning.com';
        if (User::where('email', $email)->exists()) {
            $this->command->info('Administrator account already exists. Its password and role were preserved.');

            return;
        }
        $password = Str::password(24);
        DB::transaction(function () use ($email, $password) {
            $user = User::forceCreate(['name' => 'Mirza Learning Administrator', 'email' => $email, 'password' => $password, 'role' => 'admin', 'email_verified_at' => now()]);
            DB::table('audit_logs')->insert(['actor_id' => $user->id, 'action' => 'admin.bootstrapped', 'subject_type' => 'user', 'subject_id' => $user->id, 'reason' => 'Owner-requested local AdministratorSeeder bootstrap', 'created_at' => now()]);
            if (! app()->runningUnitTests()) {
                if (! Storage::disk('local')->put('admin-bootstrap.txt', "Mirza Learning administrator\n\nEmail: {$email}\nInitial password: {$password}\n\nOpen /admin and sign in. First-time access opens /admin/setup to connect and confirm your authenticator. Save the recovery codes, then open administration. Successful sign-in confirms your password automatically. Change the initial password and remove this file after saving it in your password manager.\n")) {
                    throw new \RuntimeException('Could not save the private initial credential file. Administrator creation was rolled back.');
                }
            }
        });
        $this->command->info('Administrator created. Initial credentials: storage/app/private/admin-bootstrap.txt (private; not committed).');
    }
}
