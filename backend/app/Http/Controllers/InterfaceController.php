<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Enums\JobStatus;
use App\Enums\JobType;
use App\Http\Requests\ConfigureInterfaceRequest;
use App\Models\AutomationJob;
use App\Models\ConfigurationChange;
use App\Models\Device;
use App\Services\AuditService;
use App\Services\AutomationApiClient;
use App\Services\DeviceService;
use Illuminate\Http\JsonResponse;

class InterfaceController extends Controller
{
    public function __construct(
        protected AutomationApiClient $apiClient,
        protected DeviceService $deviceService,
        protected AuditService $auditService
    ) {}

    /**
     * GET /api/devices/{device}/interfaces
     */
    public function index(Device $device): JsonResponse
    {
        return response()->json(
            $device->interfaces()->orderBy('name')->get()
        );
    }

    /**
     * POST /api/devices/{device}/interfaces/refresh
     */
    public function refresh(Device $device): JsonResponse
    {
        $job = $this->deviceService->refreshInterfaces($device);

        return response()->json([
            'message' => $job->status->value === 'success' ? 'Interfaces refreshed' : 'Refresh failed',
            'job' => $job,
            'interfaces' => $device->fresh()->interfaces,
        ]);
    }

    /**
     * POST /api/devices/{device}/interfaces/preview
     * Generate a configuration preview WITHOUT applying it.
     */
    public function preview(ConfigureInterfaceRequest $request, Device $device): JsonResponse
    {
        $configLines = $this->buildConfigLines($request->validated());

        // Create pending change record
        $change = ConfigurationChange::create([
            'device_id' => $device->id,
            'user_id' => auth()->id(),
            'change_type' => 'interface_config',
            'configuration' => implode("\n", $configLines),
            'status' => 'pending',
        ]);

        return response()->json([
            'change_id' => $change->id,
            'device' => [
                'hostname' => $device->hostname,
                'management_ip' => $device->management_ip,
            ],
            'interface' => $request->interface_name,
            'config_preview' => $configLines,
            'config_text' => implode("\n", $configLines),
        ]);
    }

    /**
     * POST /api/devices/{device}/interfaces/deploy
     * Apply a previously previewed configuration.
     */
    public function deploy(Device $device): JsonResponse
    {
        $request = request();
        $request->validate([
            'change_id' => ['required', 'exists:configuration_changes,id'],
        ]);

        $change = ConfigurationChange::findOrFail($request->change_id);

        // Verify the change belongs to this device and is still pending
        if ($change->device_id !== $device->id || $change->status !== 'pending') {
            return response()->json([
                'message' => 'Invalid or already processed configuration change.',
            ], 422);
        }

        // Step 1: Automatic pre-change backup
        $backupJob = $this->deviceService->backupConfig($device, 'pre_change');
        if ($backupJob->status->value === 'success') {
            $backup = $device->latestBackup;
            $change->update(['backup_id' => $backup?->id]);
        }

        // Step 2: Create automation job
        $job = AutomationJob::create([
            'user_id' => auth()->id(),
            'device_id' => $device->id,
            'job_type' => JobType::CONFIGURE_INTERFACE->value,
            'status' => JobStatus::RUNNING,
            'started_at' => now(),
        ]);

        $change->update([
            'job_id' => $job->id,
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        try {
            // Step 3: Apply configuration via FastAPI
            $params = $this->apiClient->buildDeviceParams($device);
            $configLines = explode("\n", $change->configuration);
            $result = $this->apiClient->configureInterface($params, $configLines);

            if ($result['success']) {
                $change->update([
                    'status' => 'deployed',
                    'deployed_at' => now(),
                    'verification_result' => $result['data']['verification'] ?? null,
                ]);

                $device->markOnline();
                $job->markSuccess('Configuration deployed successfully');

                $this->auditService->logAction(
                    action: AuditAction::DEPLOY_CONFIG,
                    objectType: 'device',
                    objectId: $device->id,
                    description: "Interface configuration deployed on {$device->hostname}"
                );

                return response()->json([
                    'message' => 'Configuration deployed successfully.',
                    'job' => $job,
                    'change' => $change,
                ]);
            } else {
                $change->update([
                    'status' => 'failed',
                    'error_message' => $result['error'],
                ]);

                $job->markFailed($result['error']);

                $this->auditService->logAction(
                    action: AuditAction::CONFIG_FAILED,
                    objectType: 'device',
                    objectId: $device->id,
                    description: "Configuration deployment failed on {$device->hostname}",
                    status: 'failed'
                );

                return response()->json([
                    'message' => 'Configuration deployment failed.',
                    'error' => $result['error'],
                    'job' => $job,
                ], 500);
            }
        } catch (\Exception $e) {
            $change->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            $job->markFailed($e->getMessage());

            return response()->json([
                'message' => 'Configuration deployment failed.',
                'job' => $job,
            ], 500);
        }
    }

    /**
     * Build Cisco configuration lines from form input.
     */
    protected function buildConfigLines(array $data): array
    {
        $lines = ["interface {$data['interface_name']}"];

        if (isset($data['description'])) {
            $lines[] = " description {$data['description']}";
        }

        if (!empty($data['ip_address']) && !empty($data['subnet_mask'])) {
            $lines[] = " ip address {$data['ip_address']} {$data['subnet_mask']}";
        }

        if ($data['admin_status'] === 'up') {
            $lines[] = " no shutdown";
        } else {
            $lines[] = " shutdown";
        }

        return $lines;
    }
}
