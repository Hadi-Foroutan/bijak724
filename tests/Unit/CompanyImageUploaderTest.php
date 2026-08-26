<?php

use App\Services\Uploads\CompanyImageUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Storage::fake('public');
    config()->set('company_uploads.disk', 'public');
    config()->set('company_uploads.root', 'companies');
});

test('it uploads replaces deletes and generates urls for company images', function () {
    $uploader = app(CompanyImageUploader::class);
    $firstImage = UploadedFile::fake()->createWithContent('first.png', validPngContent());

    $firstPath = $uploader->upload($firstImage, 17, 'drivers');

    expect($firstPath)->toStartWith('companies/17/drivers/')
        ->and($uploader->exists($firstPath))->toBeTrue()
        ->and($uploader->url($firstPath))->toContain('/storage/'.$firstPath);

    $secondImage = UploadedFile::fake()->createWithContent('second.png', validPngContent());
    $secondPath = $uploader->replace($firstPath, $secondImage, 17, 'drivers');

    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists($secondPath);

    expect($uploader->delete($secondPath))->toBeTrue();
    Storage::disk('public')->assertMissing($secondPath);
});

test('it refuses to access files outside managed company folders', function () {
    app(CompanyImageUploader::class)->delete('unmanaged/photo.jpg');
})->throws(InvalidArgumentException::class);

function validPngContent(): string
{
    return (string) base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Z1pAAAAAASUVORK5CYII=',
        true,
    );
}
