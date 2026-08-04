<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponses;

    public function register(Request $request): JsonResponse
    {
        $validator = validator($request->all(), ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:users'], 'password' => ['required', 'string', 'min:8', 'confirmed']]);
        if ($validator->fails()) {
            return $this->failure('Registrasi tidak valid.', $validator->errors()->toArray(), 'VALIDATION_ERROR', 422);
        }
        $user = User::query()->create($validator->validated());

        return $this->success(['user' => $this->userData($user), 'token' => $user->createToken('mobile')->plainTextToken], 'Registrasi berhasil.', 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = validator($request->all(), ['email' => ['required', 'email'], 'password' => ['required', 'string'], 'device_name' => ['nullable', 'string', 'max:100']]);
        if ($validator->fails()) {
            return $this->failure('Login tidak valid.', $validator->errors()->toArray(), 'VALIDATION_ERROR', 422);
        }
        $user = User::query()->where('email', $request->email)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->failure('Email atau kata sandi salah.', [], 'UNAUTHENTICATED', 401);
        }

        return $this->success(['user' => $this->userData($user), 'token' => $user->createToken($request->input('device_name', 'mobile'))->plainTextToken], 'Login berhasil.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();
        Auth::forgetGuards();

        return $this->success(null, 'Logout berhasil.');
    }

    public function user(Request $request): JsonResponse
    {
        return $this->success($this->userData($request->user()));
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->delete();
        });

        return $this->success(null, 'Akun berhasil dihapus.');
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = validator($request->all(), ['email' => ['required', 'email']]);
        if ($validator->fails()) {
            return $this->failure('Email tidak valid.', $validator->errors()->toArray(), 'VALIDATION_ERROR', 422);
        }
        Password::sendResetLink($request->only('email'));

        return $this->success(null, 'Jika email terdaftar, tautan reset telah dikirim.');
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validator = validator($request->all(), ['token' => ['required'], 'email' => ['required', 'email'], 'password' => ['required', 'min:8', 'confirmed']]);
        if ($validator->fails()) {
            return $this->failure('Reset kata sandi tidak valid.', $validator->errors()->toArray(), 'VALIDATION_ERROR', 422);
        }
        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password): void {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET ? $this->success(null, 'Kata sandi berhasil diubah.') : $this->failure('Token reset tidak valid.', [], 'INVALID_RESET_TOKEN', 422);
    }

    private function userData(User $user): array
    {
        return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email];
    }
}
