<?php

use App\Enums\StatusEnum;
use App\Http\Middleware\CheckPermission;
use App\Models\Company;
use App\Models\DriverLicenseType;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(CheckPermission::class);
    Storage::fake('public');
    config()->set('company_uploads.disk', 'public');

    $this->user = User::query()->forceCreate([
        'national_code' => fake()->unique()->numerify('##########'),
        'first_name' => 'Test',
        'last_name' => 'User',
        'phone' => fake()->unique()->numerify('09#########'),
        'email' => fake()->unique()->safeEmail(),
        'username' => fake()->unique()->userName(),
        'password' => 'password',
    ]);

    $this->company = Company::query()->forceCreate([
        'parent_type' => 'original',
        'panel_code' => fake()->unique()->numerify('#####'),
        'organization_code' => fake()->unique()->numerify('##########'),
        'name' => 'شرکت تست تصاویر راننده',
        'national_code' => fake()->unique()->numerify('###########'),
        'city_code' => '1101',
    ]);

    $token = $this->user->createToken(
        'company-session',
        ['company-support', "company:{$this->company->id}"],
        now()->addMinutes(30),
    )->plainTextToken;
    $this->withToken($token);

    $this->licenseType = DriverLicenseType::query()->create([
        'name' => 'پایه یک',
        'code' => 1,
    ]);

    $this->driverPayload = [
        'national_code' => '1234567891',
        'first_name' => 'علی',
        'last_name' => 'احمدی',
        'father_name' => 'رضا',
        'license_number' => 'LIC-IMAGE-1',
        'license_type' => $this->licenseType->id,
        'license_expiry_date' => '2028-08-17',
        'phone_number_1' => '09121234567',
        'status' => StatusEnum::ACTIVE->value,
    ];
});

test('it stores replaces and deletes a driver profile image inside its company folder', function () {
    $createResponse = $this->post('/api/user/drivers', [
        ...$this->driverPayload,
        'profile_image' => driverProfileImage('profile.png'),
    ], ['Accept' => 'application/json'])
        ->assertCreated();

    $driverId = $createResponse->json('data.id');
    $driverTable = "company_{$this->company->id}_drivers";
    $firstPath = DB::table($driverTable)->where('id', $driverId)->value('profile_image_path');

    expect($firstPath)->toStartWith("companies/{$this->company->id}/drivers/")
        ->and($createResponse->json('data.profile_image_url'))->toContain('/storage/'.$firstPath);
    Storage::disk('public')->assertExists($firstPath);

    $updateResponse = $this->post("/api/user/drivers/{$driverId}", [
        '_method' => 'PATCH',
        'profile_image' => driverProfileImage('replacement.png'),
    ], ['Accept' => 'application/json'])
        ->assertSuccessful();

    $secondPath = DB::table($driverTable)->where('id', $driverId)->value('profile_image_path');

    expect($secondPath)->not->toBe($firstPath)
        ->and($updateResponse->json('data.profile_image_url'))->toContain('/storage/'.$secondPath);
    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($secondPath);

    $this->deleteJson("/api/user/drivers/{$driverId}")->assertSuccessful();
    Storage::disk('public')->assertMissing($secondPath);
});

test('profile image is optional and can be removed during driver update', function () {
    $createResponse = $this->postJson('/api/user/drivers', $this->driverPayload)
        ->assertCreated()
        ->assertJsonPath('data.profile_image_url', null);

    $driverId = $createResponse->json('data.id');

    $uploadResponse = $this->post("/api/user/drivers/{$driverId}", [
        '_method' => 'PATCH',
        'profile_image' => driverProfileImage('profile.png'),
    ], ['Accept' => 'application/json'])->assertSuccessful();

    $path = DB::table("company_{$this->company->id}_drivers")
        ->where('id', $driverId)
        ->value('profile_image_path');

    expect($uploadResponse->json('data.profile_image_url'))->not->toBeNull();

    $this->patchJson("/api/user/drivers/{$driverId}", [
        'remove_profile_image' => true,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.profile_image_url', null);

    Storage::disk('public')->assertMissing($path);
});

function driverProfileImage(string $name): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        $name,
        (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Z1pAAAAAASUVORK5CYII=',
            true,
        ),
    );
}
