<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bale Bot
    |--------------------------------------------------------------------------
    |
    | The bot token from @BotFather in Bale. Messages, alarms and the bot's
    | webhook all use this token. Leave it empty to disable delivery.
    |
    */

    'token' => env('BALE_BOT_TOKEN'),

    'base_url' => env('BALE_API_URL', 'https://tapi.bale.ai'),

    /*
    | When set, the webhook URL must contain this secret: /bale/webhook/{secret}.
    | For local development run `php artisan bale:poll` instead of a webhook.
    */

    'webhook_secret' => env('BALE_WEBHOOK_SECRET'),

    'bot_username' => env('BALE_BOT_USERNAME'),

];
