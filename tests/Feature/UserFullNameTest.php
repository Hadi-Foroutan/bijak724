<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('it sets full name when a user is created', function () {
    $user = User::factory()->create([
        'first_name' => 'Taylor',
        'last_name' => 'Otwell',
        'full_name' => 'Manual Name',
    ]);

    expect($user->refresh()->full_name)->toBe('Taylor Otwell');
});

test('it refreshes full name when user names change', function () {
    $user = User::factory()->create([
        'first_name' => 'Initial',
        'last_name' => 'Person',
    ]);

    $user->update([
        'first_name' => '  Updated',
        'last_name' => 'User  ',
        'full_name' => 'Ignored Name',
    ]);

    expect($user->refresh()->full_name)->toBe('Updated User');
});
