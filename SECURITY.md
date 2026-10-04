# Security Policy

This application handles sensitive personal and payroll data. If you discover a vulnerability,
**do not open a public issue**. Please report it through
[GitHub private vulnerability reporting](https://github.com/gregamano3/hris_attendance_payroll/security/advisories/new).

We aim to acknowledge reports within 72 hours and to release a fix as soon as practical.

## Supported versions

Only the latest `master` is supported.

## Data protection

### Encryption at rest

Personally identifiable information is encrypted by the application before it reaches the database or disk,
using Laravel's encrypter (AES-256-CBC with an HMAC, keyed by `APP_KEY`):

| Data | Protection |
|------|------------|
| Employee birth date, mobile, address | Encrypted columns |
| SSS, PhilHealth, Pag-IBIG numbers and TIN | Encrypted columns + HMAC-SHA256 **blind index** (`*_bidx`) for uniqueness and exact lookup |
| Bank account name and number | Encrypted columns |
| Two-factor secrets and recovery codes | Encrypted columns |
| Uploaded 201 documents | Encrypted files on the private disk, decrypted only after an authorization check |
| Passwords | Bcrypt hashes |

Database dumps and backups therefore contain ciphertext only. The audit log records *that* an encrypted field
changed (`[encrypted]`), never its value. Names and employee numbers stay in plaintext so they can be searched.

### Keys

- `APP_KEY` encrypts the data. **Back it up separately from the database:** without it encrypted data is unrecoverable.
- `BLIND_INDEX_KEY` keys the blind indexes. Set it explicitly (`php -r "echo bin2hex(random_bytes(32));"`); if unset it is derived from `APP_KEY`.

### Key rotation

1. Put the current key in `APP_PREVIOUS_KEYS` (comma separated) and generate a new `APP_KEY`.
2. Run `php artisan security:reencrypt` (`--dry-run` to preview). It rewrites all encrypted columns and documents with the new key and refreshes the blind indexes.
3. Remove the old key from `APP_PREVIOUS_KEYS`.

Use the same procedure after changing `BLIND_INDEX_KEY`.
