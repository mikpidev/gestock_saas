<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

test('the broken sales pagination-data route is not registered', function () {
    expect(Route::has('stores.sales.data'))->toBeFalse();
});

test('guests receive 404 for the removed sales pagination-data endpoint', function () {
    $this->get('/stores/1/pagination-data')
        ->assertNotFound();
});

test('authenticated users receive 404 for the removed sales pagination-data endpoint', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/stores/1/pagination-data')
        ->assertNotFound();
});
