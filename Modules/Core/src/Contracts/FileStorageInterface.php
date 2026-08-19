<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use Illuminate\Http\UploadedFile;

interface FileStorageInterface
{
    public function put(string $path, mixed $contents): bool;

    public function delete(string $path): bool;

    public function exists(string $path): bool;

    public function url(string $path): string;

    public function get(string $path): ?string;

    public function store(UploadedFile $file, string $directory): string;

    public function disk(string $disk): static;
}
