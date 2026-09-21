<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeviceRequest;
use App\Http\Requests\UpdateDeviceRequest;
use App\Models\Device;
use App\Models\Site;
use App\Services\DeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function __construct(
        protected DeviceService $deviceService
    ) {}

    /**
     * GET /api/devices
     */
    public function index(Request $request): JsonResponse
    {
        $query = Device::with('site')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('hostname', 'ilike', "%{$search}%")
                      ->orWhere('management_ip', 'ilike', "%{$search}%")
                      ->orWhere('model', 'ilike', "%{$search}%");
                });
            })
            ->when($request->category, fn ($q, $cat) => $q->where('category', $cat))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->site_id, fn ($q, $siteId) => $q->where('site_id', $siteId))
            ->orderBy('hostname');

        $devices = $query->paginate($request->get('per_page', 20));

        return response()->json($devices);
    }

    /**
     * POST /api/devices
     */
    public function store(StoreDeviceRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['username', 'password', 'enable_secret']);
        $credentials = $request->safe()->only(['username', 'password', 'enable_secret']);

        $device = $this->deviceService->createDevice($data, $credentials);

        return response()->json([
            'message' => 'Device created successfully.',
            'device' => $device,
        ], 201);
    }

    /**
     * GET /api/devices/{device}
     */
    public function show(Device $device): JsonResponse
    {
        $device->load(['site', 'latestFact', 'latestBackup']);

        return response()->json([
            'id' => $device->id,
            'hostname' => $device->hostname,
            'management_ip' => $device->management_ip,
            'ssh_port' => $device->ssh_port,
            'vendor' => $device->vendor,
            'category' => $device->category,
            'device_type' => $device->device_type,
            'model' => $device->model,
            'serial_number' => $device->serial_number,
            'os_name' => $device->os_name,
            'os_version' => $device->os_version,
            'description' => $device->description,
            'status' => $device->status,
            'last_seen_at' => $device->last_seen_at,
            'created_at' => $device->created_at,
            'site' => $device->site,
            'latest_fact' => $device->latestFact,
            'latest_backup' => $device->latestBackup ? [
                'id' => $device->latestBackup->id,
                'filename' => $device->latestBackup->filename,
                'created_at' => $device->latestBackup->created_at,
                'status' => $device->latestBackup->status,
            ] : null,
            // Credentials are NEVER included in responses
            'has_credentials' => $device->credential()->exists(),
        ]);
    }

    /**
     * PUT /api/devices/{device}
     */
    public function update(UpdateDeviceRequest $request, Device $device): JsonResponse
    {
        $data = $request->safe()->except(['username', 'password', 'enable_secret']);
        $credentials = null;

        if ($request->has('username') || $request->has('password')) {
            $credentials = $request->safe()->only(['username', 'password', 'enable_secret']);
        }

        $device = $this->deviceService->updateDevice($device, $data, $credentials);

        return response()->json([
            'message' => 'Device updated successfully.',
            'device' => $device,
        ]);
    }

    /**
     * DELETE /api/devices/{device}
     */
    public function destroy(Device $device): JsonResponse
    {
        $this->deviceService->deleteDevice($device);

        return response()->json([
            'message' => 'Device deleted successfully.',
        ]);
    }

    /**
     * POST /api/devices/{device}/test
     */
    public function test(Device $device): JsonResponse
    {
        $job = $this->deviceService->testConnection($device);

        return response()->json([
            'message' => $job->status->value === 'success' ? 'Connection successful' : 'Connection failed',
            'job' => $job,
            'device_status' => $device->fresh()->status,
        ]);
    }

    /**
     * POST /api/devices/{device}/discover
     */
    public function discover(Device $device): JsonResponse
    {
        $job = $this->deviceService->discoverDevice($device);

        return response()->json([
            'message' => $job->status->value === 'success' ? 'Discovery successful' : 'Discovery failed',
            'job' => $job,
            'device' => $device->fresh()->load('latestFact'),
        ]);
    }

    /**
     * GET /api/sites
     */
    public function sites(): JsonResponse
    {
        return response()->json(Site::orderBy('name')->get());
    }
}
