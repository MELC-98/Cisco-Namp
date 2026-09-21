<?php

namespace App\Http\Controllers;

use App\Models\ConfigurationBackup;
use App\Models\Device;
use App\Services\DeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(
        protected DeviceService $deviceService
    ) {}

    /**
     * GET /api/backups
     */
    public function index(Request $request): JsonResponse
    {
        $backups = ConfigurationBackup::with(['device:id,hostname', 'user:id,name'])
            ->when($request->device_id, fn ($q, $id) => $q->where('device_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->backup_type, fn ($q, $t) => $q->where('backup_type', $t))
            ->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json($backups);
    }

    /**
     * POST /api/devices/{device}/backups
     */
    public function store(Device $device): JsonResponse
    {
        $job = $this->deviceService->backupConfig($device);

        return response()->json([
            'message' => $job->status->value === 'success' ? 'Backup created' : 'Backup failed',
            'job' => $job,
        ], $job->status->value === 'success' ? 201 : 500);
    }

    /**
     * GET /api/backups/{backup}
     */
    public function show(ConfigurationBackup $backup): JsonResponse
    {
        $content = null;

        if ($backup->status === 'success' && file_exists($backup->file_path)) {
            $content = file_get_contents($backup->file_path);
        }

        return response()->json([
            'backup' => $backup->load(['device:id,hostname', 'user:id,name']),
            'content' => $content,
        ]);
    }

    /**
     * GET /api/backups/{backup}/download
     */
    public function download(ConfigurationBackup $backup): BinaryFileResponse|JsonResponse
    {
        if (!file_exists($backup->file_path)) {
            return response()->json(['message' => 'Backup file not found.'], 404);
        }

        return response()->download($backup->file_path, $backup->filename);
    }

    /**
     * GET /api/backups/{backup}/compare/{backup2}
     */
    public function compare(ConfigurationBackup $backup, ConfigurationBackup $backup2): JsonResponse
    {
        $content1 = file_exists($backup->file_path) ? file_get_contents($backup->file_path) : '';
        $content2 = file_exists($backup2->file_path) ? file_get_contents($backup2->file_path) : '';

        return response()->json([
            'backup1' => [
                'id' => $backup->id,
                'filename' => $backup->filename,
                'created_at' => $backup->created_at,
                'content' => $content1,
            ],
            'backup2' => [
                'id' => $backup2->id,
                'filename' => $backup2->filename,
                'created_at' => $backup2->created_at,
                'content' => $content2,
            ],
        ]);
    }
}
