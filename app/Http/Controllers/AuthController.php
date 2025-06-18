<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'referral_code' => Str::random(10),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $user->sendEmailVerificationNotification();

        return response()->json([
            'user' => $user,
            'token' => $token,
            'referral_link' => url('/register?ref=' . $user->referral_code),
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function user(Request $request)
    {
        return $request->user();
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);
        return response()->json($user);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'new_password' => 'required|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        $user = $request->user();

        $user->update(['password' => Hash::make($request->new_password)]);
        return response()->json(['message' => 'Password changed successfully'], 200);
    }

    // Новый эндпоинт для запроса сброса пароля
    public function forgotPassword(Request $request)
    {
        //\Log::info('Forgot Password Request: ', $request->all()); // Логируем запрос
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        //\Log::info('Password Reset Status: ' . $status); // Логируем статус

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)])
            : response()->json(['message' => __($status)], 400);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed', // confirmed требует password_confirmation
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Вы можете убрать эту строку, если не хотите отправлять уведомление о сбросе пароля
                // $user->sendPasswordResetNotification($password);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password reset successfully'], 200);
        }

        throw ValidationException::withMessages([
            'error' => [__($status)],
        ]);
    }
}
