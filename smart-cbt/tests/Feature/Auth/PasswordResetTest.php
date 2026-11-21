<?php

use Illuminate\Support\Facades\Route;

test('login screen does not show forgot password link', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
    $response->assertDontSee('/forgot-password');
    $response->assertDontSee('Forgot your password?');
    expect(Route::has('password.request'))->toBeFalse();
});

test('password reset routes are disabled', function () {
    $this->get('/forgot-password')->assertNotFound();
    $this->post('/forgot-password', ['email' => 'test@example.com'])->assertNotFound();
    $this->get('/reset-password/fake-token')->assertNotFound();
    $this->post('/reset-password', [
        'token' => 'fake-token',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();
});
