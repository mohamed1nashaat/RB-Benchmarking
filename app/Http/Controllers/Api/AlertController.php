<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Services\AlertService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AlertController extends Controller
{
    private AlertService $alertService;

    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    /**
     * Get all alerts for the current tenant
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $tenantId = session('current_tenant_id')
                ?? (app()->bound('current_tenant_id') ? app('current_tenant_id') : null);

            // For super admins without tenant context, get alerts from request or show all
            if (!$tenantId && auth()->user()?->is_super_admin) {
                $tenantId = $request->get('tenant_id');
            }

            if (!$tenantId) {
                // Return empty list for super admins without tenant filter
                if (auth()->user()?->is_super_admin) {
                    return response()->json([
                        'data' => [],
                        'total' => 0,
                        'message' => 'Please select a client to view alerts',
                    ]);
                }

                return response()->json([
                    'error' => 'Tenant ID not found'
                ], 400);
            }

            $filters = [
                'type' => $request->get('type'),
                'objective' => $request->get('objective'),
                'is_active' => $request->get('is_active'),
            ];

            $alerts = $this->alertService->getAlertsForTenant($tenantId, array_filter($filters));

            return response()->json([
                'data' => $alerts,
                'total' => $alerts->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching alerts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch alerts',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single alert
     */
    public function show(Alert $alert): JsonResponse
    {
        try {
            return response()->json([
                'data' => $alert,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching alert', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to fetch alert',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new alert
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'type' => 'required|in:threshold,anomaly,budget',
                'objective' => 'nullable|in:awareness,leads,sales,calls',
                'conditions' => 'required|array',
                'notification_channels' => 'required|array',
                'notification_channels.*' => 'in:email,slack,whatsapp',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }

            $tenantId = session('current_tenant_id')
                ?? (app()->bound('current_tenant_id') ? app('current_tenant_id') : null)
                ?? $request->get('tenant_id');

            if (!$tenantId) {
                return response()->json([
                    'error' => 'Tenant ID not found. Please select a client first.'
                ], 400);
            }

            $data = $request->only([
                'name',
                'type',
                'objective',
                'conditions',
                'notification_channels',
                'is_active',
            ]);

            $data['tenant_id'] = $tenantId;
            $data['user_id'] = auth()->id();

            $alert = $this->alertService->createAlert($data);

            Log::info('Alert created', [
                'alert_id' => $alert->id,
                'alert_name' => $alert->name,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Alert created successfully',
                'data' => $alert,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating alert', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to create alert',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an alert
     */
    public function update(Request $request, Alert $alert): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'type' => 'sometimes|required|in:threshold,anomaly,budget',
                'objective' => 'nullable|in:awareness,leads,sales,calls',
                'conditions' => 'sometimes|required|array',
                'notification_channels' => 'sometimes|required|array',
                'notification_channels.*' => 'in:email,slack,whatsapp',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }

            $data = $request->only([
                'name',
                'type',
                'objective',
                'conditions',
                'notification_channels',
                'is_active',
            ]);

            $alert = $this->alertService->updateAlert($alert, $data);

            Log::info('Alert updated', [
                'alert_id' => $alert->id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Alert updated successfully',
                'data' => $alert,
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating alert', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to update alert',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an alert
     */
    public function destroy(Alert $alert): JsonResponse
    {
        try {
            $this->alertService->deleteAlert($alert);

            Log::info('Alert deleted', [
                'alert_id' => $alert->id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Alert deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting alert', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to delete alert',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle alert active status
     */
    public function toggle(Alert $alert): JsonResponse
    {
        try {
            if ($alert->isActive()) {
                $alert->deactivate();
                $message = 'Alert deactivated successfully';
            } else {
                $alert->activate();
                $message = 'Alert activated successfully';
            }

            Log::info('Alert toggled', [
                'alert_id' => $alert->id,
                'is_active' => $alert->is_active,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => $message,
                'data' => $alert->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling alert', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to toggle alert',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Manually trigger alert evaluation
     */
    public function evaluate(Request $request): JsonResponse
    {
        try {
            $tenantId = session('current_tenant_id')
                ?? (app()->bound('current_tenant_id') ? app('current_tenant_id') : null)
                ?? $request->get('tenant_id');

            if (!$tenantId) {
                return response()->json([
                    'error' => 'Tenant ID not found. Please select a client first.'
                ], 400);
            }

            $results = $this->alertService->evaluateAlerts($tenantId);

            return response()->json([
                'message' => 'Alerts evaluated successfully',
                'data' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('Error evaluating alerts', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to evaluate alerts',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
