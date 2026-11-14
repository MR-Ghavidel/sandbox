<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeployRunnerController extends Controller
{
    /**
     * Allow-listed artisan commands: route slug => [command, parameters].
     *
     * @var array<string, array{0: string, 1: array<string, bool>}>
     */
    private const COMMANDS = [
        'migrate' => ['migrate', ['--force' => true]],
        'migrate-status' => ['migrate:status', []],
        'optimize' => ['optimize', []],
        'optimize-clear' => ['optimize:clear', []],
        'config-cache' => ['config:cache', []],
        'config-clear' => ['config:clear', []],
        'route-cache' => ['route:cache', []],
        'route-clear' => ['route:clear', []],
        'view-cache' => ['view:cache', []],
        'view-clear' => ['view:clear', []],
        'cache-clear' => ['cache:clear', []],
        'storage-link' => ['storage:link', []],
        'about' => ['about', []],
    ];

    public function __invoke(Request $request, string $command): JsonResponse
    {
        $token = (string) config('deploy.token');

        // No token configured means the runner is switched off.
        abort_if($token === '', 404);

        abort_unless(hash_equals($token, $this->providedToken($request)), 403);

        $definition = self::COMMANDS[$command] ?? null;

        if ($definition === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Unknown command.',
                'commands' => array_keys(self::COMMANDS),
            ], 422);
        }

        [$artisanCommand, $parameters] = $definition;

        try {
            $exitCode = Artisan::call($artisanCommand, $parameters);
            $output = Artisan::output();
            $error = null;
        } catch (Throwable $exception) {
            $exitCode = 1;
            $output = '';
            $error = $exception->getMessage();
        }

        Log::info('Deploy runner executed.', [
            'command' => $artisanCommand,
            'exit_code' => $exitCode,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'ok' => $exitCode === 0,
            'command' => $artisanCommand,
            'exit_code' => $exitCode,
            'output' => trim($output),
            'error' => $error,
        ], $exitCode === 0 ? 200 : 500);
    }

    private function providedToken(Request $request): string
    {
        return (string) ($request->header('X-Deploy-Token') ?? $request->input('token'));
    }
}
