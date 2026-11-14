<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Deploy Runner
    |--------------------------------------------------------------------------
    |
    | Shared cPanel hosts often have no terminal, so the deploy runner route
    | lets you run a small set of allow-listed artisan commands over HTTP.
    | Leave the token empty to disable the route completely.
    |
    */

    'token' => env('DEPLOY_RUNNER_TOKEN'),

];
