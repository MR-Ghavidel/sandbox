<?php

namespace App\Http\Controllers;

use App\Services\Bale\BaleBot;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class BaleWebhookController extends Controller
{
    public function __invoke(Request $request, BaleBot $bot): Response
    {
        $secret = (string) config('bale.webhook_secret');

        if ($secret !== '' && ! hash_equals($secret, (string) $request->route('secret'))) {
            abort(404);
        }

        try {
            $bot->handleUpdate($request->all());
        } catch (Throwable $exception) {
            // Never let an update fail the webhook; Bale would keep retrying it.
            Log::error('Bale webhook failed.', [
                'message' => $exception->getMessage(),
                'update' => $request->all(),
            ]);
        }

        return response('ok');
    }
}
