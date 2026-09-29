<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Services\BusinessPolicies;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        abort_if(app()->isProduction() && ! BusinessPolicies::ready(), 503, 'Registration is awaiting approved business policies.');
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'terms' => ['required', 'accepted'],
            'role' => ['prohibited'],
            'is_admin' => ['prohibited'],
            'email_verified_at' => ['prohibited'],
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => strtolower(trim($input['email'])),
            'password' => $input['password'],
        ]);
        $user->forceFill(['terms_accepted_at' => now(), 'terms_version' => config('platform.terms_version')])->save();

        return $user;
    }
}
