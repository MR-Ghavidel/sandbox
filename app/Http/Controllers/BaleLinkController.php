<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BaleLinkController extends Controller
{
    /**
     * Create a short-lived code the user sends to the bot: /start CODE.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->baleLinkCodes()->delete();

        $user->baleLinkCodes()->create([
            'code' => Str::upper(Str::random(8)),
            'expires_at' => now()->addMinutes(30),
        ]);

        return back()->with('status', 'کد اتصال ساخته شد. آن را در ربات بله بفرستید.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->baleSubscriber()->delete();

        return back()->with('status', 'اتصال ربات قطع شد. برای اتصال دوباره کد تازه بگیرید.');
    }
}
