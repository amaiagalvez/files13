<?php

namespace Files13\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Support\Str;
use Files13\Models\Document;
use Files13\Models\DocumentTrack;
use Illuminate\Http\JsonResponse;
use Files13\Support\OnlyOfficeToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Basics13\Http\Controllers\Controller;
use Files13\Http\Requests\DocumentEditorRequest;
use Files13\Http\Requests\OnlyOfficeCallbackRequest;
use Files13\Http\Requests\OnlyOfficeDownloadRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Editing documents with OnlyOffice: the editor page, and the two endpoints the document server
 * calls, the download of the content and the callback that reports the session and saves.
 */
class OnlyOfficeController extends Controller
{
    /** How long the signed URLs handed to the document server stay valid, in seconds. */
    private const TOKEN_TTL = 12 * 3600;

    public function editor(DocumentEditorRequest $request, Document $document): View
    {
        $secret = OnlyOfficeToken::secret();
        $server = rtrim((string) config('files13.onlyoffice.server'), '/');

        if ($secret === null || $server === '') {
            return view('files13::documents.editor', ['document' => $document, 'config' => null, 'server' => null]);
        }

        $readOnly = ! $request->writable($document);
        $user = Auth::user();
        $urlToken = OnlyOfficeToken::encode([
            'document' => $document->id,
            'readonly' => $readOnly,
            'user' => $user?->getAuthIdentifier(),
            'exp' => time() + self::TOKEN_TTL,
        ], $secret);

        $config = [
            'document' => [
                'fileType' => $document->extension,
                'title' => $document->name.($document->extension ? '.'.$document->extension : ''),
                'url' => route('onlyoffice.get', ['document' => $document, 'token' => $urlToken]),
                'key' => $document->key,
                'permissions' => ['edit' => ! $readOnly, 'download' => true, 'print' => true, 'comment' => ! $readOnly],
            ],
            'documentType' => self::documentType((string) $document->extension),
            'editorConfig' => [
                'mode' => $readOnly ? 'view' : 'edit',
                'callbackUrl' => route('onlyoffice.callback', ['document' => $document, 'token' => $urlToken]),
                'lang' => app()->getLocale(),
                'user' => ['id' => (string) $user?->getAuthIdentifier(), 'name' => (string) $user?->getAttribute('name')],
            ],
        ];
        $config['token'] = OnlyOfficeToken::encode($config, $secret);

        DocumentTrack::record($document, 'read');

        return view('files13::documents.editor', ['document' => $document, 'config' => $config, 'server' => $server]);
    }

    public function get(OnlyOfficeDownloadRequest $request, Document $document): StreamedResponse
    {
        abort_if($document->path === null || ! Storage::disk(Document::disk())->exists($document->path), 404);

        return Storage::disk(Document::disk())->download(
            $document->path,
            Str::slug($document->name).($document->extension ? '.'.$document->extension : ''),
        );
    }

    public function callback(OnlyOfficeCallbackRequest $request, Document $document): JsonResponse
    {
        $status = (int) $request->validated()['status'];

        if ($status === OnlyOfficeCallbackRequest::EDITING && ! $document->editing) {
            $document->forceFill(['editing' => true])->save();
        }

        if ($status === OnlyOfficeCallbackRequest::CLOSED_WITHOUT_CHANGES) {
            $document->forceFill(['editing' => false])->save();
        }

        if ($status === OnlyOfficeCallbackRequest::READY_FOR_SAVING) {
            $document->forceFill(['editing' => false])->save();

            // A read-only session never writes, whatever the editor sends.
            if ($request->readOnly()) {
                return response()->json(['error' => 0]);
            }

            $url = (string) ($request->validated()['url'] ?? '');

            if (! self::fromDocumentServer($url)) {
                return response()->json(['error' => 1, 'message' => 'The content does not come from the document server.'], 403);
            }

            $response = Http::withoutRedirecting()->timeout(30)->get($url);

            if (! $response->successful()) {
                return response()->json(['error' => 1, 'message' => 'The document server did not send the content.'], 502);
            }

            $path = $document->path ?? 'files13/'.Str::random(40).($document->extension ? '.'.$document->extension : '');
            Storage::disk(Document::disk())->put($path, $response->body());
            $document->forceFill(['path' => $path, 'key' => Str::random(40)])->save();
            DocumentTrack::record($document, 'write', $request->userId());
        }

        return response()->json(['error' => 0]);
    }

    /**
     * Whether a URL points at the configured document server: same scheme, host and port.
     */
    private static function fromDocumentServer(string $url): bool
    {
        $server = parse_url((string) config('files13.onlyoffice.server'));
        $target = parse_url($url);

        if (! is_array($server) || ! is_array($target) || ! isset($server['host'], $target['host'])) {
            return false;
        }

        return strtolower($server['host']) === strtolower($target['host'])
            && ($server['scheme'] ?? 'https') === ($target['scheme'] ?? '')
            && ($server['port'] ?? null) === ($target['port'] ?? null);
    }

    /**
     * The editor OnlyOffice opens a format with.
     */
    private static function documentType(string $extension): string
    {
        return match (true) {
            in_array($extension, ['csv', 'fods', 'ods', 'ots', 'xls', 'xlsb', 'xlsm', 'xlsx', 'xlt', 'xltm', 'xltx'], true) => 'cell',
            in_array($extension, ['fodp', 'odp', 'otp', 'pot', 'potm', 'potx', 'pps', 'ppsm', 'ppsx', 'ppt', 'pptm', 'pptx'], true) => 'slide',
            $extension === 'pdf' => 'pdf',
            default => 'word',
        };
    }
}
