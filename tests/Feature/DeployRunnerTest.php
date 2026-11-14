<?php

namespace Tests\Feature;

use Tests\TestCase;

class DeployRunnerTest extends TestCase
{
    public function test_the_runner_is_hidden_when_no_token_is_configured(): void
    {
        config(['deploy.token' => null]);

        $this->get('/deploy/artisan/optimize-clear')->assertNotFound();
    }

    public function test_a_wrong_token_is_rejected(): void
    {
        config(['deploy.token' => 'super-secret']);

        $this->get('/deploy/artisan/optimize-clear?token=wrong')->assertForbidden();
    }

    public function test_unknown_commands_are_refused(): void
    {
        config(['deploy.token' => 'super-secret']);

        $this->getJson('/deploy/artisan/db-wipe?token=super-secret')
            ->assertStatus(422)
            ->assertJsonPath('ok', false);
    }

    public function test_an_allow_listed_command_runs_with_the_token(): void
    {
        config(['deploy.token' => 'super-secret']);

        $this->getJson('/deploy/artisan/version', ['X-Deploy-Token' => 'super-secret'])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('command', 'version');
    }

    public function test_the_token_can_come_from_the_query_string(): void
    {
        config(['deploy.token' => 'super-secret']);

        $this->getJson('/deploy/artisan/optimize-clear?token=super-secret')
            ->assertOk()
            ->assertJsonPath('ok', true);
    }
}
