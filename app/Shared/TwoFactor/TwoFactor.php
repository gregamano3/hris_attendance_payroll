<?php

namespace App\Shared\TwoFactor;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * TOTP (RFC 6238) helpers shared by the account and login slices.
 */
class TwoFactor
{
    public function __construct(private Google2FA $engine) {}

    public function generateSecret(): string
    {
        return $this->engine->generateSecretKey(32);
    }

    /**
     * Accepts the current code and one step either side (clock drift).
     */
    public function verify(string $secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        return preg_match('/^\d{6}$/', $code) === 1 && $this->engine->verifyKey($secret, $code, 1) !== false;
    }

    public function currentCode(string $secret): string
    {
        return $this->engine->getCurrentOtp($secret);
    }

    public function qrCodeSvg(User $user, string $secret): string
    {
        $url = $this->engine->getQRCodeUrl((string) config('app.name'), $user->email, $secret);

        return (new Writer(new ImageRenderer(new RendererStyle(192, 1), new SvgImageBackEnd)))->writeString($url);
    }

    /**
     * @return Collection<int, string>
     */
    public function recoveryCodes(int $count = 8): Collection
    {
        /** @var Collection<int, string> $codes */
        $codes = Collection::times($count, fn (): string => Str::lower(Str::random(5).'-'.Str::random(5)));

        return $codes;
    }

    /**
     * Roles that must use two-factor authentication (REQUIRE_2FA_ROLES).
     */
    public function isRequiredFor(User $user): bool
    {
        $roles = array_filter(array_map('trim', explode(',', (string) config('hris.require_two_factor_roles'))));

        return $roles !== [] && $user->hasAnyRole($roles);
    }
}
