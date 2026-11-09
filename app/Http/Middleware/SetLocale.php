<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the interface language chosen with the language switch. Bahasa
 * Indonesia is the default (APP_LOCALE); English is the alternative.
 */
final class SetLocale
{
    public const SESSION_KEY = 'locale';

    public const SUPPORTED = ['id', 'en'];

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null;

        if (is_string($locale) && in_array($locale, self::SUPPORTED, true)) {
            app()->setLocale($locale);
        }

        Carbon::setLocale(app()->getLocale());
        CarbonImmutable::setLocale(app()->getLocale());

        return $next($request);
    }
}
