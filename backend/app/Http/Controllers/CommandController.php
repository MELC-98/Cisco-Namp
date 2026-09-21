<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Enums\JobStatus;
use App\Enums\JobType;
use App\Models\AutomationJob;
use App\Models\CommandResult;
use App\Models\Device;
use App\Services\AuditService;
use App\Services\AutomationApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommandController extends Controller
{
    public function __construct(
        protected AutomationApiClient $apiClient,
        protected AuditService $auditService
    ) {}

    /**
     * GET /api/commands/available
     * Return the whitelist of approved show commands.
     */
    public function available(): JsonResponse
    {
        return response()->json([
            'commands' => config('automation.approved_commands'),
        ]);
    }

    /**
     * POST /api/devices/{device}/commands
     * Execute an approved show command on a device.
     */
    public function execute(Request $request, Device $device): JsonResponse
    {
        $request->validate([
            'command' => ['required', 'string'],
        ]);

        $command = strtolower(trim($request->command));

        // Validate against whitelist
        $approved = array_map('strtolower', config('automation.approved_commands'));
        if (!in_array($command, $approved)) {
            return response()->json([
                'message' => 'This command is not in the approved command list.',
            ], 422);
        }

        // Block configuration commands (defense in depth)
        $blocked = ['configure', 'conf t', 'write', 'copy', 'delete', 'erase', 'reload', 'shutdown'];
        foreach ($blocked as $blockedCmd) {
            if (str_starts_with($command, $blockedCmd)) {
                return response()->json([
                    'message' => 'Configuration commands are not allowed through this interface.',
                ], 403);
            }
        }

        // Create job
        $job = AutomationJob::create([
            'user_id' => auth()->id(),
            'device_id' => $device->id,
            'job_type' => JobType::RUN_COMMAND->value,
            'status' => JobStatus::RUNNING,
            'started_at' => now(),
        ]);

        try {
            $params = $this->apiClient->buildDeviceParams($device);
            $result = $this->apiClient->runCommand($params, $request->command);

            $commandResult = CommandResult::create([
                'device_id' => $device->id,
                'user_id' => auth()->id(),
                'job_id' => $job->id,
                'command' => $request->command,
                'output' => $result['success'] ? ($result['data']['output'] ?? '') : null,
                'status' => $result['success'] ? 'success' : 'failed',
                'error_message' => $result['success'] ? null : $result['error'],
            ]);

            if ($result['success']) {
                $device->markOnline();
                $job->markSuccess($result['data']['output'] ?? '');
            } else {
                $job->markFailed($result['error']);
            }

            $this->auditService->logAction(
                action: AuditAction::RUN_COMMAND,
                objectType: 'device',
                objectId: $device->id,
                description: "Executed: {$request->command} on {$device->hostname}",
                status: $result['success'] ? 'success' : 'failed'
            );

            return response()->json([
                'job' => $job->fresh(),
                'result' => $commandResult,
            ]);
        } catch (\Exception $e) {
            $job->markFailed($e->getMessage());

            return response()->json([
                'message' => 'Command execution failed.',
                'job' => $job,
            ], 500);
        }
    }
    /**
     * POST /api/devices/{device}/configure-raw
     * Deploy raw configuration to a device.
     */
    public function configureRaw(Request $request, Device $device): JsonResponse
    {
        $request->validate([
            'config_lines' => ['required', 'string'],
        ]);

        $lines = array_filter(array_map('trim', explode("\n", $request->config_lines)));
        
        if (empty($lines)) {
            return response()->json(['message' => 'Configuration cannot be empty.'], 422);
        }

        // Create job
        $job = AutomationJob::create([
            'user_id' => auth()->id(),
            'device_id' => $device->id,
            'job_type' => JobType::CONFIGURE_DEVICE->value,
            'status' => JobStatus::RUNNING,
            'started_at' => now(),
        ]);

        try {
            $params = $this->apiClient->buildDeviceParams($device);
            $result = $this->apiClient->configureRaw($params, array_values($lines));

            if ($result['success']) {
                $device->markOnline();
                $job->markSuccess($result['data']['message'] ?? 'Configuration applied successfully');
            } else {
                $job->markFailed($result['error']);
            }

            $this->auditService->logAction(
                action: AuditAction::CONFIGURE_DEVICE,
                objectType: 'device',
                objectId: $device->id,
                description: "Deployed raw configuration to {$device->hostname} (" . count($lines) . " lines)",
                status: $result['success'] ? 'success' : 'failed'
            );

            return response()->json([
                'job' => $job->fresh(),
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            $job->markFailed($e->getMessage());

            return response()->json([
                'message' => 'Configuration deployment failed.',
                'job' => $job,
            ], 500);
        }
    }
    /**
     * GET /api/devices/{device}/commands
     * Command execution history for a device.
     */
    public function history(Device $device, Request $request): JsonResponse
    {
        $results = CommandResult::where('device_id', $device->id)
            ->with('user:id,name')
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($results);
    }
}
