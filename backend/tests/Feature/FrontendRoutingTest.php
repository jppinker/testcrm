<?php

namespace Tests\Feature;

use Tests\TestCase;

class FrontendRoutingTest extends TestCase
{
    public function test_frontend_routes_return_the_same_shell_when_built(): void
    {
        if (!is_file(public_path('app/index.html'))) {
            $this->markTestSkipped('Run npm run build:laravel in frontend to test the built shell.');
        }

        $this->get('/')->assertOk()->assertSee('<div id="app"></div>', false);
        $this->get('/schedules/example')->assertOk()->assertSee('<div id="app"></div>', false);
    }

    public function test_api_and_missing_assets_are_not_frontend_routes(): void
    {
        $this->getJson('/api/no-such-endpoint')->assertNotFound();
        $this->get('/app/assets/missing.js')->assertNotFound();
        $this->get('/missing.js')->assertNotFound();
        $this->get('/up')->assertOk();
    }
}
