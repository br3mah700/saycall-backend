<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Domain AJAX access
    |--------------------------------------------------------------------------
    |
    | Lists the allowed requests whose `Access-Control-Allow-Origin` header
    | will be sent. Can be a single, wildcard, or an array of origins.
    |
    */

    'allowed_origins' => ['http://localhost:8081', 'exp://localhost:8081', 'http://127.0.0.1:8081', 'exp://127.0.0.1:8081', 'http://localhost:3000', 'http://127.0.0.1:3000'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins Patterns
    |--------------------------------------------------------------------------
    |
    | Use this option if you want to allow origins that follow a pattern
    | For example, you might use '*.example.com' to allow all subdomains.
    |
    */

    'allowed_origins_patterns' => ['/^https?:\/\/localhost:/', '/^exp:\/\//', '/^http:\/\/127\.0\.0\.1:/'],

    /*
    |--------------------------------------------------------------------------
    | Allowed HTTP Methods
    |--------------------------------------------------------------------------
    |
    | The HTTP methods that are allowed for CORS requests. This applies to
    | the OPTIONS preflight request.
    |
    */

    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed HTTP Headers
    |--------------------------------------------------------------------------
    |
    | The HTTP headers that are allowed for CORS requests. This applies to
    | the OPTIONS preflight request.
    |
    */

    'allowed_headers' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Exposed HTTP Headers
    |--------------------------------------------------------------------------
    |
    | The HTTP headers that are allowed to be exposed to the browser. This
    | applies to the actual CORS request.
    |
    */

    'exposed_headers' => [],

    /*
    |--------------------------------------------------------------------------
    | CORS Max Age
    |--------------------------------------------------------------------------
    |
    | The number of seconds the browser is allowed to cache the CORS
    | preflight response.
    |
    */

    'max_age' => 86400,

    /*
    |--------------------------------------------------------------------------
    | Support Credentials
    |--------------------------------------------------------------------------
    |
    | Whether the response needs to support credentials. This is useful when
    | the requests need to include cookies or other forms of authentication.
    |
    */

    'supports_credentials' => false,

    /*
    |--------------------------------------------------------------------------
    | CORS Paths
    |--------------------------------------------------------------------------
    |
    | The paths for which CORS support is enabled. This applies only to the
    | paths listed here.
    |
    */

    'paths' => ['graphql', 'graphiql'],

];
