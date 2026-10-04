<?php

namespace App\Shared\Security;

/**
 * Deterministic keyed hash (HMAC-SHA256) of a normalised value so encrypted
 * columns can still be checked for uniqueness and looked up exactly, without
 * storing the plaintext. Uses BLIND_INDEX_KEY (falls back to a key derived
 * from APP_KEY — set it explicitly so APP_KEY rotations don't change hashes).
 */
final class BlindIndex
{
    public static function hash(string $context, ?string $value): ?string
    {
        $value = $value === null ? '' : trim($value);

        return $value === '' ? null : hash_hmac('sha256', $context.'|'.$value, self::key());
    }

    private static function key(): string
    {
        $key = (string) config('hris.security.blind_index_key');

        return $key !== '' ? $key : hash('sha256', 'blind-index|'.config('app.key'));
    }
}
