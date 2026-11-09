<?php

declare(strict_types=1);

namespace App\Http\Support;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Str;

/**
 * Ties analyses to the browser that created them. There are no accounts: the
 * encrypted session cookie holds a random token, and analyses store only its
 * SHA-256 hash. Without the cookie, an analysis can't be opened.
 */
final class AnalysisOwner
{
    private const SESSION_KEY = 'cekpdp.owner';

    public function __construct(
        private readonly Session $session,
    ) {}

    /**
     * The visitor's owner hash, creating their token on first use.
     */
    public function hash(): string
    {
        $token = $this->session->get(self::SESSION_KEY);

        if (! is_string($token) || strlen($token) < 40) {
            $token = Str::random(64);
            $this->session->put(self::SESSION_KEY, $token);
        }

        return hash('sha256', $token);
    }

    /**
     * The visitor's owner hash, or null if they have never submitted anything.
     */
    public function existingHash(): ?string
    {
        $token = $this->session->get(self::SESSION_KEY);

        return is_string($token) && strlen($token) >= 40 ? hash('sha256', $token) : null;
    }
}
