<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const OTP_TTL_MINUTES = 5;

    /**
     * POST /api/v1/auth/send-otp
     * Body: { phone: "0901234567" }
     */
    public function sendOtp(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'phone' => ['required', 'string', 'regex:/^(\+84|0)[0-9]{9}$/'],
            ]);

            $phone = $data['phone'];

            Log::channel('daily')->info('[Auth] sendOtp request', [
                'phone' => $phone,
                'ip'    => $request->ip(),
            ]);

            // Invalidate any prior unused OTPs for this phone
            OtpCode::where('phone', $phone)->where('used', false)->delete();

            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            OtpCode::create([
                'phone'      => $phone,
                'code'       => $code,
                'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
            ]);

            // TODO: integrate SMS provider (Vonage / Twilio). For dev, we log the code.
            Log::channel('daily')->info("[Auth] OTP generated for {$phone}: {$code}");

            $response = ['message' => 'OTP sent successfully.'];

            // In non-production, return OTP directly for easier dev/testing
            if (! app()->isProduction()) {
                $response['otp'] = $code;
            }

            return response()->json($response);
        } catch (\Throwable $e) {
            Log::channel('daily')->error('[Auth] sendOtp failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip'    => $request->ip(),
            ]);
            throw $e;
        }
    }

    /**
     * POST /api/v1/auth/verify-otp
     * Body: { phone, otp }
     * Creates user if not exists, returns Sanctum token.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'phone' => ['required', 'string', 'regex:/^(\+84|0)[0-9]{9}$/'],
                'otp'   => ['required', 'string', 'size:6'],
                'name'  => ['nullable', 'string', 'max:100'],
            ]);

            Log::channel('daily')->info('[Auth] verifyOtp request', [
                'phone' => $data['phone'],
                'ip'    => $request->ip(),
            ]);

            $otpRecord = OtpCode::where('phone', $data['phone'])
                ->where('code', $data['otp'])
                ->where('used', false)
                ->latest()
                ->first();

            if (! $otpRecord || ! $otpRecord->isValid()) {
                Log::channel('daily')->warning('[Auth] verifyOtp invalid/expired OTP', [
                    'phone' => $data['phone'],
                ]);
                throw ValidationException::withMessages([
                    'otp' => ['Mã OTP không hợp lệ hoặc đã hết hạn.'],
                ]);
            }

            // Mark OTP used
            $otpRecord->update(['used' => true]);

            $user = User::firstOrCreate(
                ['phone' => $data['phone']],
                ['name'  => $data['name'] ?? null],
            );

            // Update name if provided and user already existed
            if (isset($data['name']) && $user->name !== $data['name']) {
                $user->update(['name' => $data['name']]);
            }

            $token = $user->createToken('mobile')->plainTextToken;

            Log::channel('daily')->info('[Auth] verifyOtp success', [
                'user_id' => $user->id,
                'phone'   => $user->phone,
            ]);

            return response()->json([
                'token' => $token,
                'user'  => [
                    'id'    => $user->id,
                    'phone' => $user->phone,
                    'name'  => $user->name,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::channel('daily')->error('[Auth] verifyOtp failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip'    => $request->ip(),
            ]);
            throw $e;
        }
    }

    /**
     * POST /api/v1/auth/logout
     * Requires: Bearer token
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Đã đăng xuất.']);
    }

    /**
     * GET /api/v1/auth/me
     * Requires: Bearer token
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id'    => $user->id,
            'phone' => $user->phone,
            'name'  => $user->name,
        ]);
    }
}
