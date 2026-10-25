<?php

namespace Files13\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets through only the addresses the document server calls from. The address is the one Laravel
 * resolves, so behind a proxy it is only as good as the host's list of trusted proxies.
 */
class TrustedOnlyOfficeIp
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var list<string> $ips */
        $ips = (array) config('files13.onlyoffice.ips', []);

        abort_unless($ips !== [] && in_array($request->ip(), $ips, true), 403);

        return $next($request);
    }
}
