<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Contracts\FileStorageInterface;

final class FileStorageService implements FileStorageInterface
{
    private string $disk = 'public';

    public function put(string $path, mixed $contents): bool
    {
        return Storage::disk($this->disk)->put($path, $contents);
    }

    public function delete(string $path): bool
    {
        return Storage::disk($this->disk)->delete($path);
    }

    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }

    public function url(string $path): string
    {
        return Storage::disk($this->disk)->url($path);
    }

    public function get(string $path): ?string
    {
        return Storage::disk($this->disk)->get($path);
    }

    public function store(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, $this->disk);
    }

    public function disk(string $disk): static
    {
        $clone = clone $this;
        $clone->disk = $disk;

        return $clone;
    }
}
