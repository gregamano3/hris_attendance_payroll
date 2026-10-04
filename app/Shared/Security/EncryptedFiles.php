<?php

namespace App\Shared\Security;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/**
 * Stores file contents encrypted with the application key (AES-256-CBC +
 * MAC via Laravel's encrypter) on a private disk.
 */
final class EncryptedFiles
{
    public static function put(string $disk, string $path, string $contents): void
    {
        Storage::disk($disk)->put($path, Crypt::encryptString(base64_encode($contents)));
    }

    public static function get(string $disk, string $path): string
    {
        return (string) base64_decode(Crypt::decryptString((string) Storage::disk($disk)->get($path)), true);
    }
}
