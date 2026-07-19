<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $actor = auth('platform')->user();
        if (! $actor) {
            return redirect()->route('control.login');
        }

        if (! $actor->mfa_enabled) {
            return redirect()->route('control.mfa.enroll');
        }

        if ($request->session()->get('mfa.verified_actor') !== 'platform:'.$actor->getKey()) {
            return redirect()->route('control.mfa.challenge');
        }

        return $next($request);
    }
}
