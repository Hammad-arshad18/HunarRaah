<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class StreamGateway
{
    private function endpoint(string $uid): string
    {
        abort_unless(config('platform.stream.account') && config('platform.stream.token'), 503, 'Video is not configured.');

        return 'https://api.cloudflare.com/client/v4/accounts/'.config('platform.stream.account').'/stream/'.$uid;
    }

    /** @return array<string, mixed> */
    public function inspect(string $uid): array
    {
        return Http::withToken(config('platform.stream.token'))->timeout(15)->get($this->endpoint($uid))->throw()->json('result');
    }

    public function token(string $uid): string
    {
        $result = Http::withToken(config('platform.stream.token'))->timeout(15)->post($this->endpoint($uid).'/token', ['exp' => now()->addMinutes(10)->timestamp])->throw()->json();
        abort_unless(($result['success'] ?? false) && ! empty($result['result']['token']), 503, 'Playback provider unavailable.');

        return $result['result']['token'];
    }
}
