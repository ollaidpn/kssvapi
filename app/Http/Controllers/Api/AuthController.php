<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\OtpCode;
use App\Mail\OtpMail;
use App\Mail\WelcomeMail;
use App\Helpers\Shortcut;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login with email and password
     * Token expiration: Admin = 60 minutes, Client = 30 jours
     */
    public function login(Request $request): JsonResponse
    {
        try {
            Log::info('API Auth: Tentative de connexion', ['email' => $request->email, 'ip' => $request->ip()]);
            
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if (!auth()->attempt($credentials)) {
                Log::warning('API Auth: Echec de connexion - identifiants incorrects', ['email' => $request->email, 'ip' => $request->ip()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiants incorrects. Veuillez vérifier votre email et mot de passe.',
                ], 401);
            }

            $user = User::where('email', $request->email)->firstOrFail();
            
            // Bloquer les comptes inactifs
            if (($user->status ?? 'active') === 'inactive') {
                Log::warning('API Auth: Tentative connexion compte inactif', ['email' => $request->email, 'user_id' => $user->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.',
                ], 403);
            }
            
            // Expiration fixe: 30 jours pour tous les types de comptes
            // La gestion d'inactivité admin se fait côté frontend si nécessaire
            $expirationMinutes = 43200; // 30 jours = 43200 minutes
            $expiresAt = now()->addMinutes($expirationMinutes);
            
            // Créer le token avec expiration
            $token = $user->createToken('auth-token', ['*'], $expiresAt);

            Log::info('API Auth: Connexion reussie', [
                'user_id' => $user->id,
                'email' => $user->email,
                'account_type' => $user->account_type,
                'token_expires_at' => $expiresAt->toDateTimeString()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'token' => $token->plainTextToken,
                'expires_at' => $expiresAt->toIso8601String(),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'ccphone' => $user->ccphone,
                    'phone' => $user->phone,
                    'account_type' => $user->account_type,
                    'reference' => $user->reference,
                    'avatar' => Shortcut::fileExistsOnServer($user->avatar),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API Auth: Erreur lors de la connexion', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la connexion.',
            ], 500);
        }
    }

    /**
     * Step 1: Send OTP for registration
     */
    public function registerSendOtp(Request $request): JsonResponse
    {
        try {
            Log::info('API Auth: Demande OTP inscription', ['email' => $request->email, 'phone' => $request->phone]);
            
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
                Log::warning('API Auth: Telephone deja utilise', ['phone' => $validated['phone']]);
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

            Log::info('API Auth: OTP inscription envoye', ['email' => $validated['email']]);

            return response()->json([
                'success' => true,
                'message' => 'Un code de vérification a été envoyé à votre adresse email.',
                'email' => $validated['email'],
            ]);
        } catch (ValidationException $e) {
            Log::warning('API Auth: Validation echouee pour inscription', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('API Auth: Erreur envoi OTP inscription', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'envoi du code.',
            ], 500);
        }
    }

    /**
     * Step 2: Verify OTP for registration
     */
    public function registerVerifyOtp(Request $request): JsonResponse
    {
        try {
            Log::info('API Auth: Verification OTP inscription', ['email' => $request->email]);
            
            $validated = $request->validate([
                'email' => 'required|email',
                'code' => 'required|string|size:6',
            ]);

            $otp = OtpCode::findValid($validated['email'], $validated['code'], 'registration');

            if (!$otp) {
                Log::warning('API Auth: OTP inscription invalide', ['email' => $validated['email']]);
                return response()->json([
                    'success' => false,
                    'message' => 'Code invalide ou expiré. Veuillez réessayer.',
                ], 422);
            }

            Log::info('API Auth: OTP inscription verifie avec succes', ['email' => $validated['email']]);

            return response()->json([
                'success' => true,
                'message' => 'Code vérifié avec succès. Vous pouvez maintenant créer votre mot de passe.',
                'email' => $validated['email'],
            ]);
        } catch (\Exception $e) {
            Log::error('API Auth: Erreur verification OTP inscription', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue.',
            ], 500);
        }
    }

    /**
     * Step 3: Complete registration with password
     */
    public function registerComplete(Request $request): JsonResponse
    {
        try {
            Log::info('API Auth: Finalisation inscription', ['email' => $request->email]);
            
            $validated = $request->validate([
                'email' => 'required|email',
                'code' => 'required|string|size:6',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $otp = OtpCode::findValid($validated['email'], $validated['code'], 'registration');

            if (!$otp) {
                Log::warning('API Auth: Session inscription expiree', ['email' => $validated['email']]);
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

            // Create token with 30 days expiration for clients
            $expiresAt = now()->addDays(30);
            $token = $user->createToken('auth-token', ['*'], $expiresAt);

            Log::info('API Auth: Nouveau compte cree avec succes', [
                'user_id' => $user->id,
                'email' => $user->email,
                'reference' => $user->reference,
                'token_expires_at' => $expiresAt->toDateTimeString()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Compte créé avec succès ! Bienvenue chez KSSV.',
                'token' => $token->plainTextToken,
                'expires_at' => $expiresAt->toIso8601String(),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'ccphone' => $user->ccphone,
                    'phone' => $user->phone,
                    'account_type' => $user->account_type,
                    'reference' => $user->reference,
                    'avatar' => Shortcut::fileExistsOnServer($user->avatar),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API Auth: Erreur creation compte', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la création du compte.',
            ], 500);
        }
    }

    /**
     * Step 1: Send OTP for password reset
     */
    public function forgotPasswordSendOtp(Request $request): JsonResponse
    {
        try {
            Log::info('API Auth: Demande reset mot de passe', ['email' => $request->email]);
            
            $validated = $request->validate([
                'email' => 'required|email|exists:users,email',
            ], [
                'email.exists' => 'Aucun compte n\'est associé à cette adresse email.',
            ]);

            // Create OTP for password reset
            $otp = OtpCode::createForPasswordReset($validated['email']);

            // Send OTP email
            Mail::to($validated['email'])->send(new OtpMail($otp->code, 'password_reset'));

            Log::info('API Auth: OTP reset mot de passe envoye', ['email' => $validated['email']]);

            return response()->json([
                'success' => true,
                'message' => 'Un code de réinitialisation a été envoyé à votre adresse email.',
                'email' => $validated['email'],
            ]);
        } catch (ValidationException $e) {
            Log::warning('API Auth: Email non trouve pour reset', ['email' => $request->email]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('API Auth: Erreur envoi OTP reset', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue.',
            ], 500);
        }
    }

    /**
     * Step 2: Verify OTP for password reset
     */
    public function forgotPasswordVerifyOtp(Request $request): JsonResponse
    {
        try {
            Log::info('API Auth: Verification OTP reset', ['email' => $request->email]);
            
            $validated = $request->validate([
                'email' => 'required|email',
                'code' => 'required|string|size:6',
            ]);

            $otp = OtpCode::findValid($validated['email'], $validated['code'], 'password_reset');

            if (!$otp) {
                Log::warning('API Auth: OTP reset invalide', ['email' => $validated['email']]);
                return response()->json([
                    'success' => false,
                    'message' => 'Code invalide ou expiré. Veuillez réessayer.',
                ], 422);
            }

            Log::info('API Auth: OTP reset verifie', ['email' => $validated['email']]);

            return response()->json([
                'success' => true,
                'message' => 'Code vérifié. Vous pouvez maintenant créer un nouveau mot de passe.',
                'email' => $validated['email'],
            ]);
        } catch (\Exception $e) {
            Log::error('API Auth: Erreur verification OTP reset', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue.',
            ], 500);
        }
    }

    /**
     * Step 3: Reset password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            Log::info('API Auth: Reset mot de passe', ['email' => $request->email]);
            
            $validated = $request->validate([
                'email' => 'required|email',
                'code' => 'required|string|size:6',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $otp = OtpCode::findValid($validated['email'], $validated['code'], 'password_reset');

            if (!$otp) {
                Log::warning('API Auth: Session reset expiree', ['email' => $validated['email']]);
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

            Log::info('API Auth: Mot de passe reinitialise avec succes', ['user_id' => $user->id, 'email' => $user->email]);

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe modifié avec succès. Vous pouvez maintenant vous connecter.',
            ]);
        } catch (\Exception $e) {
            Log::error('API Auth: Erreur reset mot de passe', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue.',
            ], 500);
        }
    }

    /**
     * Get current authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        Log::debug('API Auth: Recuperation profil utilisateur', ['user_id' => $user->id]);

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
                'avatar' => Shortcut::fileExistsOnServer($user->avatar),
            ],
        ]);
    }

    /**
     * Check if token is still valid (polling endpoint for session verification)
     */
    public function checkToken(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                Log::debug('API Auth: Token check failed - no user');
                return response()->json([
                    'success' => false,
                    'connected' => false,
                    'message' => 'Token expiré ou invalide'
                ], 401);
            }

            Log::debug('API Auth: Token check success', ['user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'connected' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'account_type' => $user->account_type,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('API Auth: Token check error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'connected' => false,
                'message' => 'Erreur de vérification'
            ], 500);
        }
    }

    /**
     * Logout user (revoke current token)
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        Log::info('API Auth: Deconnexion', ['user_id' => $user->id, 'email' => $user->email]);
        
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
        try {
            Log::info('API Auth: Demande renvoi OTP', ['email' => $request->email, 'type' => $request->type]);
            
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
                    Log::warning('API Auth: Session inscription expiree pour renvoi OTP', ['email' => $validated['email']]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Session expirée. Veuillez recommencer l\'inscription.',
                    ], 422);
                }

                $otp = OtpCode::createForRegistration($validated['email'], $existingOtp->pending_data);
            } else {
                // Check if user exists
                if (!User::where('email', $validated['email'])->exists()) {
                    Log::warning('API Auth: Email non trouve pour renvoi OTP reset', ['email' => $validated['email']]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Aucun compte n\'est associé à cette adresse email.',
                    ], 422);
                }

                $otp = OtpCode::createForPasswordReset($validated['email']);
            }

            // Send OTP email
            Mail::to($validated['email'])->send(new OtpMail($otp->code, $validated['type']));

            Log::info('API Auth: OTP renvoye avec succes', ['email' => $validated['email'], 'type' => $validated['type']]);

            return response()->json([
                'success' => true,
                'message' => 'Un nouveau code a été envoyé à votre adresse email.',
            ]);
        } catch (\Exception $e) {
            Log::error('API Auth: Erreur renvoi OTP', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue.',
            ], 500);
        }
    }

    /**
     * PUT /api/auth/profile
     * Mettre à jour les informations du client
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            Log::info('API Auth: Mise a jour profil', ['user_id' => $user->id]);
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'ccphone' => 'required|string|max:5',
                'phone' => 'required|string|max:20',
            ]);

            $user->update($validated);

            Log::info('API Auth: Profil mis a jour avec succes', ['user_id' => $user->id, 'changes' => $validated]);

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
        } catch (\Exception $e) {
            Log::error('API Auth: Erreur mise a jour profil', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue.',
            ], 500);
        }
    }

    /**
     * PUT /api/auth/password
     * Changer le mot de passe du client connecté
     */
    public function updatePassword(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            Log::info('API Auth: Demande changement mot de passe', ['user_id' => $user->id]);
            
            $validated = $request->validate([
                'current_password' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            // Vérifier le mot de passe actuel
            if (!Hash::check($validated['current_password'], $user->password)) {
                Log::warning('API Auth: Mot de passe actuel incorrect', ['user_id' => $user->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Le mot de passe actuel est incorrect.',
                ], 422);
            }

            // Mettre à jour le mot de passe
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);

            Log::info('API Auth: Mot de passe change avec succes', ['user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'Mot de passe modifié avec succès.',
            ]);
        } catch (\Exception $e) {
            Log::error('API Auth: Erreur changement mot de passe', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue.',
            ], 500);
        }
    }
}
