<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\OtpCode;
use App\Mail\OtpMail;
use App\Mail\WelcomeMail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login with email and password
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!auth()->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrects. Veuillez vérifier votre email et mot de passe.',
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth-token');

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'token' => $token->plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'ccphone' => $user->ccphone,
                'phone' => $user->phone,
                'account_type' => $user->account_type,
                'reference' => $user->reference,
                'avatar' => $user->avatar,
            ],
        ]);
    }

    /**
     * Step 1: Send OTP for registration
     */
    public function registerSendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'ccphone' => 'required|string|max:5',
            'phone' => 'required|string|max:20',
        ], [
            'email.unique' => 'Cette adresse email est déjà utilisée.',
        ]);

        // Check if phone already exists
        $existingPhone = User::where('ccphone', $validated['ccphone'])
            ->where('phone', $validated['phone'])
            ->exists();

        if ($existingPhone) {
            return response()->json([
                'success' => false,
                'message' => 'Ce numéro de téléphone est déjà utilisé.',
            ], 422);
        }

        // Create OTP with pending registration data
        $otp = OtpCode::createForRegistration($validated['email'], [
            'name' => $validated['name'],
            'ccphone' => $validated['ccphone'],
            'phone' => $validated['phone'],
        ]);

        // Send OTP email
        Mail::to($validated['email'])->send(new OtpMail($otp->code, 'registration'));

        return response()->json([
            'success' => true,
            'message' => 'Un code de vérification a été envoyé à votre adresse email.',
            'email' => $validated['email'],
        ]);
    }

    /**
     * Step 2: Verify OTP for registration
     */
    public function registerVerifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $otp = OtpCode::findValid($validated['email'], $validated['code'], 'registration');

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Code invalide ou expiré. Veuillez réessayer.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Code vérifié avec succès. Vous pouvez maintenant créer votre mot de passe.',
            'email' => $validated['email'],
        ]);
    }

    /**
     * Step 3: Complete registration with password
     */
    public function registerComplete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $otp = OtpCode::findValid($validated['email'], $validated['code'], 'registration');

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Session expirée. Veuillez recommencer l\'inscription.',
            ], 422);
        }

        // Create user with pending data
        $pendingData = $otp->pending_data;
        
        $user = User::create([
            'name' => $pendingData['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'ccphone' => $pendingData['ccphone'],
            'phone' => $pendingData['phone'],
            'account_type' => 'client',
            'reference' => 'KSSV-' . strtoupper(Str::random(6)),
        ]);

        // Mark OTP as used
        $otp->markAsUsed();

        // Send welcome email
        Mail::to($user->email)->send(new WelcomeMail($user));

        // Create token
        $token = $user->createToken('auth-token');

        return response()->json([
            'success' => true,
            'message' => 'Compte créé avec succès ! Bienvenue chez KSSV.',
            'token' => $token->plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'ccphone' => $user->ccphone,
                'phone' => $user->phone,
                'account_type' => $user->account_type,
                'reference' => $user->reference,
                'avatar' => $user->avatar,
            ],
        ]);
    }

    /**
     * Step 1: Send OTP for password reset
     */
    public function forgotPasswordSendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'Aucun compte n\'est associé à cette adresse email.',
        ]);

        // Create OTP for password reset
        $otp = OtpCode::createForPasswordReset($validated['email']);

        // Send OTP email
        Mail::to($validated['email'])->send(new OtpMail($otp->code, 'password_reset'));

        return response()->json([
            'success' => true,
            'message' => 'Un code de réinitialisation a été envoyé à votre adresse email.',
            'email' => $validated['email'],
        ]);
    }

    /**
     * Step 2: Verify OTP for password reset
     */
    public function forgotPasswordVerifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $otp = OtpCode::findValid($validated['email'], $validated['code'], 'password_reset');

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Code invalide ou expiré. Veuillez réessayer.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Code vérifié. Vous pouvez maintenant créer un nouveau mot de passe.',
            'email' => $validated['email'],
        ]);
    }

    /**
     * Step 3: Reset password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $otp = OtpCode::findValid($validated['email'], $validated['code'], 'password_reset');

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Session expirée. Veuillez recommencer la procédure.',
            ], 422);
        }

        // Update user password
        $user = User::where('email', $validated['email'])->firstOrFail();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Mark OTP as used
        $otp->markAsUsed();

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe modifié avec succès. Vous pouvez maintenant vous connecter.',
        ]);
    }

    /**
     * Get current authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'ccphone' => $user->ccphone,
                'phone' => $user->phone,
                'account_type' => $user->account_type,
                'reference' => $user->reference,
                'avatar' => $user->avatar,
            ],
        ]);
    }

    /**
     * Logout user (revoke current token)
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie.',
        ]);
    }

    /**
     * Resend OTP code
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'type' => 'required|in:registration,password_reset',
        ]);

        if ($validated['type'] === 'registration') {
            // Get existing pending data
            $existingOtp = OtpCode::where('email', $validated['email'])
                ->where('type', 'registration')
                ->latest()
                ->first();

            if (!$existingOtp || !$existingOtp->pending_data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session expirée. Veuillez recommencer l\'inscription.',
                ], 422);
            }

            $otp = OtpCode::createForRegistration($validated['email'], $existingOtp->pending_data);
        } else {
            // Check if user exists
            if (!User::where('email', $validated['email'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun compte n\'est associé à cette adresse email.',
                ], 422);
            }

            $otp = OtpCode::createForPasswordReset($validated['email']);
        }

        // Send OTP email
        Mail::to($validated['email'])->send(new OtpMail($otp->code, $validated['type']));

        return response()->json([
            'success' => true,
            'message' => 'Un nouveau code a été envoyé à votre adresse email.',
        ]);
    }

    /**
     * PUT /api/auth/profile
     * Mettre à jour les informations du client
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ccphone' => 'required|string|max:5',
            'phone' => 'required|string|max:20',
        ]);

        $user = $request->user();
        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'ccphone' => $user->ccphone,
                'phone' => $user->phone,
                'account_type' => $user->account_type,
                'reference' => $user->reference,
                'avatar' => $user->avatar,
            ],
        ]);
    }

    /**
     * PUT /api/auth/password
     * Changer le mot de passe du client connecté
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        // Vérifier le mot de passe actuel
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Le mot de passe actuel est incorrect.',
            ], 422);
        }

        // Mettre à jour le mot de passe
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe modifié avec succès.',
        ]);
    }
}
