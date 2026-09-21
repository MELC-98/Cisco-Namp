<?php

namespace App\Http\Controllers;

use App\Models\AutomationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobController extends Controller
{
    /**
     * GET /api/jobs
     */
    public function index(Request $request): JsonResponse
    {
        $jobs = AutomationJob::with(['device:id,hostname', 'user:id,name'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->device_id, fn ($q, $id) => $q->where('device_id', $id))
            ->when($request->job_type, fn ($q, $t) => $q->where('job_type', $t))
            ->when($request->user_id, fn ($q, $id) => $q->where('user_id', $id))
            ->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json($jobs);
    }

    /**
     * GET /api/jobs/{job}
     */
    public function show(AutomationJob $job): JsonResponse
    {
        $job->load(['device:id,hostname,management_ip', 'user:id,name,email']);

        return response()->json([
            'id' => $job->id,
            'device' => $job->device,
            'user' => $job->user,
            'job_type' => $job->job_type,
            'status' => $job->status,
            'output' => $job->output,
            'error' => $job->error,
            'started_at' => $job->started_at,
            'finished_at' => $job->finished_at,
            'duration' => $job->duration,
            'created_at' => $job->created_at,
        ]);
    }
}
