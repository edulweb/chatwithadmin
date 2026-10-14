<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountActivationNotification;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyNewEmailNotification;
use App\Support\UrlHelper;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubfolderUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_url_helper_generates_root_urls(): void
    {
        config(['app.frontend_url' => 'https://domain.com']);

        $url = UrlHelper::frontendUrl();
        $this->assertEquals('https://domain.com', $url);

        $activation = new AccountActivationNotification('token123');
        $this->assertEquals('https://domain.com/activate-account?token=token123', $activation->activationUrl);

        $reset = new ResetPasswordNotification('token456', 'user@example.com');
        $this->assertEquals('https://domain.com/reset-password?token=token456&email=user%40example.com', $reset->resetUrl);

        $verify = new VerifyNewEmailNotification('token789', 'new@example.com');
        $this->assertEquals('https://domain.com/verify-email-change?token=token789', $verify->verifyUrl);
    }

    public function test_url_helper_generates_connectly_subfolder_urls(): void
    {
        config(['app.frontend_url' => 'https://domain.com/connectly']);

        $url = UrlHelper::frontendUrl();
        $this->assertEquals('https://domain.com/connectly', $url);

        $activation = new AccountActivationNotification('token123');
        $this->assertEquals('https://domain.com/connectly/activate-account?token=token123', $activation->activationUrl);

        $reset = new ResetPasswordNotification('token456', 'user@example.com');
        $this->assertEquals('https://domain.com/connectly/reset-password?token=token456&email=user%40example.com', $reset->resetUrl);

        $verify = new VerifyNewEmailNotification('token789', 'new@example.com');
        $this->assertEquals('https://domain.com/connectly/verify-email-change?token=token789', $verify->verifyUrl);
    }

    public function test_url_helper_generates_generic_subfolder_urls(): void
    {
        config(['app.frontend_url' => 'https://domain.com/my-subdir']);

        $url = UrlHelper::frontendUrl();
        $this->assertEquals('https://domain.com/my-subdir', $url);

        $activation = new AccountActivationNotification('token123');
        $this->assertEquals('https://domain.com/my-subdir/activate-account?token=token123', $activation->activationUrl);
    }

    public function test_spa_serves_root_routes_with_root_base_href(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('<base href="/">', false);
        $response->assertSee('"basePath":"\/"', false);

        $responseChat = $this->get('/');
        $responseChat->assertStatus(200);
        $responseChat->assertSee('<base href="/">', false);

        $responseActivate = $this->get('/activate-account');
        $responseActivate->assertStatus(200);
        $responseActivate->assertSee('<base href="/">', false);
    }

    public function test_spa_catches_connectly_subfolder_routes(): void
    {
        $response = $this->get('/connectly');
        $response->assertStatus(200);
        $response->assertSee('<base href="/connectly/">', false);

        $responseLogin = $this->get('/connectly/login');
        $responseLogin->assertStatus(200);
        $responseLogin->assertSee('<base href="/connectly/">', false);

        $responseActivate = $this->get('/connectly/activate-account');
        $responseActivate->assertStatus(200);
        $responseActivate->assertSee('<base href="/connectly/">', false);

        $responseReset = $this->get('/connectly/reset-password');
        $responseReset->assertStatus(200);
        $responseReset->assertSee('<base href="/connectly/">', false);
    }

    public function test_spa_catches_generic_subfolder_routes(): void
    {
        $response = $this->get('/my-subdir');
        $response->assertStatus(200);
        $response->assertSee('<base href="/my-subdir/">', false);

        $responseLogin = $this->get('/my-subdir/login');
        $responseLogin->assertStatus(200);
        $responseLogin->assertSee('<base href="/my-subdir/">', false);
    }
}
