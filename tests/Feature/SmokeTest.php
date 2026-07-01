<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Minimal end-to-end smoke: the app boots, the schema migrates on MySQL, and
// routing + auth middleware behave. Replaces the removed Laravel Breeze
// starter-kit tests, which exercised web auth screens this API backend does
// not ship.

it('serves a public endpoint without auth', function () {
    $this->getJson('/api/v1/public/categories')->assertSuccessful();
});

it('rejects unauthenticated access to a protected endpoint', function () {
    $this->getJson('/api/v1/my-books')->assertUnauthorized();
});
