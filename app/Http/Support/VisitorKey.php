<?php

declare(strict_types=1);

namespace App\Http\Support;

use Illuminate\Http\Request;

/**
 * A pseudonymous key for rate limiting: a keyed hash (HMAC with APP_KEY) of the
 * visitor's IP address. Unlike a plain hash, it can't be reversed by trying
 * every IP address, and it is kept only as long as the rate-limit window.
 */
final class VisitorKey
{
    public static function for(Request $request): string
    {
        return hash_hmac('sha256', (string) $request->ip(), config()->string('app.key'));
    }
}
