<?php

return [

    /*
    |--------------------------------------------------------------------------
    | FlySend API Key
    |--------------------------------------------------------------------------
    |
    | Your FlySend API key used to authenticate requests. You can find this
    | in your FlySend dashboard under API Keys.
    |
    */

    'api_key' => env('FLYSEND_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | FlySend API Endpoint
    |--------------------------------------------------------------------------
    |
    | The base URL for the FlySend API. You should not need to change this
    | unless you are using a self-hosted instance of FlySend.
    |
    */

    'endpoint' => env('FLYSEND_API_ENDPOINT', 'https://api.flysend.co'),

];
