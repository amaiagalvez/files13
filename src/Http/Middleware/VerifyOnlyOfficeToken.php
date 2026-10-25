<?php

namespace Files13\Http\Middleware;

use Closure;
use Files13\Models\Document;
use Illuminate\Http\Request;
use Files13\Support\OnlyOfficeToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * The download and callback URLs the editor page hands to OnlyOffice carry a token signed with the
 * secret, for that document. A request without one, with a bad signature or for another document
 * is refused; the payload travels on to the controller, with the read-only flag in it.
 */
class VerifyOnlyOfficeToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = OnlyOfficeToken::secret();
        $token = $request->query('token');
        $payload = $secret !== null && is_string($token) ? OnlyOfficeToken::decode($token, $secret) : null;
        $document = $request->route('document');

        abort_unless(
            $payload !== null && $document instanceof Document && (int) ($payload['document'] ?? 0) === $document->id,
            403,
        );

        $request->attributes->set('onlyoffice', $payload);

        return $next($request);
    }
}
