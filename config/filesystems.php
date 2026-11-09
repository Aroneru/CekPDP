<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Filesystem
    |--------------------------------------------------------------------------
    |
    | CekPDP never stores uploaded policies: files are parsed from PHP's own
    | temporary upload during the request and discarded when it ends. Only the
    | private "local" disk exists, for framework internals. There is no public
    | disk, cloud disk or storage symlink, and the local disk is not served.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

    ],

    'links' => [],

];
