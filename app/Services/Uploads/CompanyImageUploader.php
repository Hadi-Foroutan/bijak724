<?php

namespace App\Services\Uploads;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

class CompanyImageUploader
{
    private string $disk;

    private string $root;

    public function __construct(?string $disk = null, ?string $root = null)
    {
        $this->disk = $disk ?? (string) config('company_uploads.disk', 'public');
        $this->root = trim($root ?? (string) config('company_uploads.root', 'companies'), '/');
    }

    public function upload(UploadedFile $image, int $companyId, string $collection): string
    {
        $path = $this->filesystem()->putFile(
            $this->directory($companyId, $collection),
            $image,
            ['visibility' => 'public'],
        );

        if ($path === false) {
            throw new RuntimeException('The company image could not be uploaded.');
        }

        return $path;
    }

    public function replace(
        ?string $currentPath,
        UploadedFile $image,
        int $companyId,
        string $collection,
    ): string {
        $newPath = $this->upload($image, $companyId, $collection);

        if (! $this->delete($currentPath)) {
            $this->delete($newPath);
        }

        return $newPath;
    }

    public function delete(?string $path): bool
    {
        if ($path === null || $path === '') {
            return true;
        }

        $this->ensureManagedPath($path);

        if (! $this->filesystem()->exists($path)) {
            return true;
        }

        return $this->filesystem()->delete($path);
    }

    public function exists(?string $path): bool
    {
        if ($path === null || $path === '') {
            return false;
        }

        $this->ensureManagedPath($path);

        return $this->filesystem()->exists($path);
    }

    public function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $this->ensureManagedPath($path);

        return $this->filesystem()->url($path);
    }

    public function directory(int $companyId, string $collection): string
    {
        if ($companyId < 1) {
            throw new InvalidArgumentException('Company ID must be greater than zero.');
        }

        $collection = trim($collection, '/');

        if ($collection === '' || preg_match('/^[a-z0-9_-]+(?:\/[a-z0-9_-]+)*$/i', $collection) !== 1) {
            throw new InvalidArgumentException('The upload collection contains invalid characters.');
        }

        return "{$this->root}/{$companyId}/{$collection}";
    }

    private function ensureManagedPath(string $path): void
    {
        if (! str_starts_with($path, $this->root.'/')) {
            throw new InvalidArgumentException('Only managed company images may be accessed.');
        }
    }

    private function filesystem(): Filesystem
    {
        return Storage::disk($this->disk);
    }
}
