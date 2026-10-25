<?php

namespace Files13\Http\Middleware;

use Closure;
use Files13\Models\Document;
use Illuminate\Http\Request;
use Files13\Support\OnlyOfficeToken;
use Basics13\Concerns\ResolvesUserModel;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * The download and callback URLs the editor page hands to OnlyOffice carry a token signed with the
 * secret, for that document. A request without one, with a bad signature or for another document
 * is refused. The token outlives the permissions it was issued with, so the user it names must
 * still be active and may still read the document, and may still change it when the token is
 * editable. An archived document is refused. The payload travels on to the controller, with the
 * read-only flag in it.
 */
class VerifyOnlyOfficeToken
{
    use ResolvesUserModel;

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

        $userModel = $this->userModelClass();
        $user = isset($payload['user']) ? $userModel::query()->whereKey($payload['user'])->where('active', true)->first() : null;

        abort_unless(
            $user !== null && $document->active && $this->mayUse($user, $document, (bool) ($payload['readonly'] ?? true)),
            403,
        );

        $request->attributes->set('onlyoffice', $payload);

        return $next($request);
    }

    /**
     * Whether the user still reads the document and, for an editable token, still changes it. The
     * same split as the editor page: templates through the template permissions, file documents
     * through their file.
     */
    private function mayUse(Authorizable $user, Document $document, bool $readOnly): bool
    {
        if ($document->template) {
            return $user->can('viewAny', Document::class) && ($readOnly || $user->can('update', $document));
        }

        $file = $document->files()->first();

        return $file !== null && $user->can('view', $file) && ($readOnly || $user->can('update', $file));
    }
}
