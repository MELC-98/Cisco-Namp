<?php

namespace App\Http\Controllers;

use App\Models\AutomationJob;
use App\Models\ConfigurationBackup;
use App\Models\Device;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard/stats
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'total_devices' => Device::count(),
            'online_devices' => Device::online()->count(),
            'offline_devices' => Device::offline()->count(),
            'routers' => Device::routers()->count(),
            'switches' => Device::switches()->count(),
            'backups_today' => ConfigurationBackup::whereDate('created_at', today())->count(),
            'failed_jobs' => AutomationJob::where('status', 'failed')
                ->whereDate('created_at', today())->count(),
            'successful_jobs' => AutomationJob::where('status', 'success')
                ->whereDate('created_at', today())->count(),
        ]);
    }

    /**
     * GET /api/dashboard/devices
     */
    public function devices(): JsonResponse
    {
        $devices = Device::with('site')
            ->orderByRaw("CASE WHEN status = 'offline' THEN 0 WHEN status = 'warning' THEN 1 ELSE 2 END")
            ->orderBy('hostname')
            ->limit(20)
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'hostname' => $d->hostname,
                'management_ip' => $d->management_ip,
                'category' => $d->category,
                'model' => $d->model,
                'status' => $d->status,
                'last_seen_at' => $d->last_seen_at,
                'site' => $d->site?->name,
            ]);

        return response()->json($devices);
    }

    /**
     * GET /api/dashboard/recent-jobs
     */
    public function recentJobs(): JsonResponse
    {
        $jobs = AutomationJob::with(['device:id,hostname', 'user:id,name'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($j) => [
                'id' => $j->id,
                'device' => $j->device?->hostname,
                'job_type' => $j->job_type,
                'user' => $j->user?->name,
                'status' => $j->status,
                'created_at' => $j->created_at,
                'duration' => $j->duration,
            ]);

        return response()->json($jobs);
    }

    /**
     * GET /api/dashboard/recent-backups
     */
    public function recentBackups(): JsonResponse
    {
        $backups = ConfigurationBackup::with(['device:id,hostname', 'user:id,name'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'device' => $b->device?->hostname,
                'user' => $b->user?->name,
                'created_at' => $b->created_at,
                'status' => $b->status,
                'backup_type' => $b->backup_type,
            ]);

        return response()->json($backups);
    }
}
