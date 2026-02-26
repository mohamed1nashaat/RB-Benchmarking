<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserInvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class InvitationController extends Controller
{
    protected UserInvitationService $invitationService;

    public function __construct(UserInvitationService $invitationService)
    {
        $this->invitationService = $invitationService;
    }

    /**
     * Verify an invitation token.
     */
    public function verify(string $token)
    {
        $result = $this->invitationService->verify($token);

        if (!$result) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid invitation token',
            ], 404);
        }

        return response()->json($result);
    }

    /**
     * Accept an invitation.
     */
    public function accept(Request $request, string $token)
    {
        // Verify token first
        $verifyResult = $this->invitationService->verify($token);

        if (!$verifyResult) {
            return response()->json([
                'message' => 'Invalid invitation token',
            ], 404);
        }

        if (!$verifyResult['valid']) {
            if ($verifyResult['expired']) {
                return response()->json([
                    'message' => 'This invitation has expired',
                ], 410);
            }
            if ($verifyResult['accepted']) {
                return response()->json([
                    'message' => 'This invitation has already been used',
                ], 410);
            }
        }

        // Validation rules differ based on whether user exists
        $rules = [];
        if (!$verifyResult['existing_user']) {
            $rules = [
                'name' => 'required|string|max:255',
                'password' => ['required', 'confirmed', Password::defaults()],
            ];
        }

        $validated = $request->validate($rules);

        try {
            $result = $this->invitationService->accept(
                $token,
                $validated['name'] ?? null,
                $validated['password'] ?? null
            );

            Log::info('Invitation accepted', [
                'user_id' => $result['user']->id,
                'tenant_id' => $result['tenant']->id,
                'is_new_user' => $result['is_new_user'],
            ]);

            // If this is a new user, they need to login
            // If existing user, they're already authenticated or need to login
            return response()->json([
                'message' => 'Invitation accepted successfully',
                'is_new_user' => $result['is_new_user'],
                'tenant' => [
                    'id' => $result['tenant']->id,
                    'name' => $result['tenant']->name,
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to accept invitation', [
                'token' => substr($token, 0, 10) . '...',
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to accept invitation',
            ], 500);
        }
    }
}
