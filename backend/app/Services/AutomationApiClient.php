<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP client for communication between Laravel and FastAPI.
 * All requests are authenticated with a shared API key.
 * Communication happens over the internal Docker network only.
 */
class AutomationApiClient
{
    protected string $baseUrl;
    protected string $apiKey;
    protected int $timeout;
    protected int $connectTimeout;

    public function __construct()
    {
        $this->baseUrl = config('automation.api_url');
        $this->apiKey = config('automation.api_key');
        $this->timeout = config('automation.timeout', 120);
        $this->connectTimeout = config('automation.connect_timeout', 10);
    }

    /**
     * Test SSH connectivity to a Cisco device.
     */
    public function testConnection(array $deviceParams): array
    {
        return $this->post('/devices/test', $deviceParams);
    }

    /**
     * Discover device facts (show version, show inventory).
     */
    public function discoverDevice(array $deviceParams): array
    {
        return $this->post('/devices/discover', $deviceParams);
    }

    /**
     * Execute an approved show command on a device.
     */
    public function runCommand(array $deviceParams, string $command): array
    {
        return $this->post('/devices/command', array_merge($deviceParams, [
            'command' => $command,
        ]));
    }

    /**
     * Backup running-config from a device.
     */
    public function backupConfig(array $deviceParams): array
    {
        return $this->post('/devices/backup', $deviceParams);
    }

    /**
     * Get interface details from a device.
     */
    public function getInterfaces(array $deviceParams): array
    {
        return $this->post('/devices/interfaces', $deviceParams);
    }

    /**
     * Apply interface configuration to a device.
     */
    public function configureInterface(array $deviceParams, array $configLines): array
    {
        return $this->post('/devices/configure-interface', array_merge($deviceParams, [
            'config_lines' => $configLines,
        ]));
    }

    /**
     * Apply raw configuration to a device.
     */
    public function configureRaw(array $deviceParams, array $configLines): array
    {
        return $this->post('/devices/configure-raw', array_merge($deviceParams, [
            'config_lines' => $configLines,
        ]));
    }

    /**
     * Check if the automation service is healthy.
     */
    public function healthCheck(): array
    {
        return $this->get('/health');
    }

    // ── Private HTTP Methods ─────────────────────────────────

    protected function post(string $endpoint, array $data): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . $endpoint, $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('detail') ?? 'Automation service error',
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Automation API request failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                // NEVER log device credentials
            ]);

            return [
                'success' => false,
                'error' => 'Failed to connect to automation service: ' . $e->getMessage(),
            ];
        }
    }

    protected function get(string $endpoint): array
    {
        try {
            $response = Http::timeout($this->connectTimeout)
                ->connectTimeout($this->connectTimeout)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                ])
                ->get($this->baseUrl . $endpoint);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('detail') ?? 'Automation service error',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Automation service unavailable',
            ];
        }
    }

    /**
     * Build device connection parameters for FastAPI.
     * Credentials are decrypted here and passed only over internal network.
     */
    public function buildDeviceParams(\App\Models\Device $device): array
    {
        $credential = $device->credential;

        if (!$credential) {
            throw new \RuntimeException("No credentials configured for device: {$device->hostname}");
        }

        return [
            'host' => $device->management_ip,
            'port' => $device->ssh_port,
            'username' => $credential->username,
            'password' => $credential->getDecryptedPassword(),
            'device_type' => $device->device_type,
            'enable_secret' => $credential->getDecryptedEnableSecret(),
        ];
    }
}
