<?php

namespace App\Features\Attendance\DeviceApi;

use App\Features\Attendance\Models\AttendanceDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $device = $request->bearerToken() ? AttendanceDevice::findByToken($request->bearerToken()) : null;

        if ($device === null) {
            return response()->json(['message' => 'Invalid or inactive device token.'], 401);
        }

        $device->forceFill(['last_seen_at' => now(), 'last_ip' => $request->ip()])->saveQuietly();
        $request->attributes->set('device', $device);

        return $next($request);
    }
}
