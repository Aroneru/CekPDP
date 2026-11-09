<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class LocaleController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $locale = $request->string('locale')->toString();

        if (in_array($locale, SetLocale::SUPPORTED, true)) {
            $request->session()->put(SetLocale::SESSION_KEY, $locale);
        }

        return redirect()->back(fallback: route('home'));
    }
}
