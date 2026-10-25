<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Document storage
    |--------------------------------------------------------------------------
    |
    | The disk the uploaded and copied documents are written to. It has to be a
    | private disk: documents are only served through the package routes, which
    | check who asks. Every file is stored under the `files13/` prefix with a
    | generated name; the original name is kept as data only.
    |
    */

    'disk' => env('FILES13_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | OnlyOffice document server
    |--------------------------------------------------------------------------
    |
    | `server` is the base URL of the document server; the callback only
    | downloads saved documents from that host. `ips` lists the addresses the
    | server calls back from, and `jwt_secret` signs and verifies every token.
    | There is no default secret: without one the editor and the callback refuse
    | to work.
    |
    */

    'onlyoffice' => [
        'server' => env('ONLYOFFICE_SERVER', ''),
        'ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('ONLYOFFICE_IPS', ''))))),
        'jwt_secret' => env('ONLYOFFICE_JWT_SECRET'),
    ],

];
