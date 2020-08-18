<?php

return [
    'http2'              => env('APP_RESOURCE_HTTP2', true),
    'cdn'                => env('APP_RESOURCE_CDN', true),
    'inline'             => env('APP_RESOURCE_INLINE', false),
    'source'             => env('APP_RESOURCE_SOURCE', 'build'),
    'manifest-revisions' => env('APP_RESOURCE_MANIFEST_REVISIONS', 'manifest-rev'),
    'manifest-integrity' => env('APP_RESOURCE_MANIFEST_INTEGRITY', 'manifest-integrity'),
];
