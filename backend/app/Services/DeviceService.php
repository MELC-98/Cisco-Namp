<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\JobStatus;
use App\Enums\JobType;
use App\Models\AutomationJob;
use App\Models\ConfigurationBackup;
use App\Models\Device;
use App\Models\DeviceFact;
use App\Models\DeviceInterface;
use Illuminate\Support\Facades\Storage;

class DeviceService
{
    public function __construct(
        protected AutomationApiClient $apiClient,
        protected AuditService $auditService
    ) {}

    /**
     * Create a new device with credentials.
     */
    public function createDevice(array $data, array $credentials): Device
    {
        $device = Device::create($data);

        // Store encrypted credentials in separate table
        $device->credential()->create([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'enable_secret' => $credentials['enable_secret'] ?? null,
        ]);

        $this->auditService->logAction(
            action: AuditAction::CREATE_DEVICE,
            objectType: 'device',
            objectId: $device->id,
            description: "Created device: {$device->hostname} ({$device->management_ip})"
        );

        return $device->load('site');
    }

    /**
     * Update a device and optionally its credentials.
     */
    public function updateDevice(Device $device, array $data, ?array $credentials = null): Device
    {
        $device->update($data);

        if ($credentials) {
            $device->credential()->updateOrCreate(
                ['device_id' => $device->id],
                [
                    'username' => $credentials['username'],
                    'password' => $credentials['password'],
                    'enable_secret' => $credentials['enable_secret'] ?? null,
                ]
            );
        }

        $this->auditService->logAction(
            action: AuditAction::UPDATE_DEVICE,
            objectType: 'device',
            objectId: $device->id,
            description: "Updated device: {$device->hostname}"
        );

        return $device->load('site');
    }

    /**
     * Delete a device and its credentials.
     */
    public function deleteDevice(Device $device): void
    {
        $hostname = $device->hostname;
        $id = $device->id;

        $device->delete(); // Cascade deletes credentials

        $this->auditService->logAction(
            action: AuditAction::DELETE_DEVICE,
            objectType: 'device',
            objectId: $id,
            description: "Deleted device: {$hostname}"
        );
    }

    /**
     * Test SSH connectivity to a device.
     */
    public function testConnection(Device $device): AutomationJob
    {
        $job = $this->createJob($device, JobType::TEST_CONNECTION);
        $job->markRunning();

        try {
            $params = $this->apiClient->buildDeviceParams($device);
            $result = $this->apiClient->testConnection($params);

            if ($result['success']) {
                $device->markOnline();
                $job->markSuccess('Connection successful');

                $this->auditService->logAction(
                    action: AuditAction::TEST_CONNECTION,
                    objectType: 'device',
                    objectId: $device->id,
                    description: "Connection test successful: {$device->hostname}"
                );
            } else {
                $device->markOffline();
                $job->markFailed($result['error']);

                $this->auditService->logAction(
                    action: AuditAction::TEST_CONNECTION,
                    objectType: 'device',
                    objectId: $device->id,
                    description: "Connection test failed: {$device->hostname}",
                    status: 'failed'
                );
            }
        } catch (\Exception $e) {
            $device->markOffline();
            $job->markFailed($e->getMessage());
        }

        return $job;
    }

    /**
     * Discover device facts via show version / show inventory.
     */
    public function discoverDevice(Device $device): AutomationJob
    {
        $job = $this->createJob($device, JobType::DISCOVER_DEVICE);
        $job->markRunning();

        try {
            $params = $this->apiClient->buildDeviceParams($device);
            $result = $this->apiClient->discoverDevice($params);

            if ($result['success']) {
                $facts = $result['data'];

                // Update device with discovered info
                $device->update([
                    'model' => $facts['model'] ?? $device->model,
                    'serial_number' => $facts['serial_number'] ?? $device->serial_number,
                    'os_name' => $facts['os_name'] ?? $device->os_name,
                    'os_version' => $facts['os_version'] ?? $device->os_version,
                    'status' => 'online',
                    'last_seen_at' => now(),
                ]);

                // Store discovery record
                DeviceFact::create([
                    'device_id' => $device->id,
                    'hostname' => $facts['hostname'] ?? null,
                    'model' => $facts['model'] ?? null,
                    'serial_number' => $facts['serial_number'] ?? null,
                    'os_version' => $facts['os_version'] ?? null,
                    'uptime' => $facts['uptime'] ?? null,
                    'hardware' => $facts['hardware'] ?? null,
                    'image_file' => $facts['image_file'] ?? null,
                    'raw_data' => $facts,
                    'discovered_at' => now(),
                ]);

                $job->markSuccess(json_encode($facts));

                $this->auditService->logAction(
                    action: AuditAction::DISCOVER_DEVICE,
                    objectType: 'device',
                    objectId: $device->id,
                    description: "Device discovery successful: {$device->hostname}"
                );
            } else {
                $job->markFailed($result['error']);
            }
        } catch (\Exception $e) {
            $job->markFailed($e->getMessage());
        }

        return $job;
    }

    /**
     * Backup running-config from a device.
     */
    public function backupConfig(Device $device, string $backupType = 'manual'): AutomationJob
    {
        $job = $this->createJob($device, JobType::BACKUP_CONFIG);
        $job->markRunning();

        try {
            $params = $this->apiClient->buildDeviceParams($device);
            $result = $this->apiClient->backupConfig($params);

            if ($result['success']) {
                $config = $result['data']['config'] ?? '';
                $filename = $device->hostname . '_' . now()->format('Y-m-d_His') . '.cfg';
                $filePath = config('automation.backup_path') . '/' . $filename;

                // Ensure directory exists and write file
                $dir = dirname($filePath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                file_put_contents($filePath, $config);

                $backup = ConfigurationBackup::create([
                    'device_id' => $device->id,
                    'user_id' => auth()->id(),
                    'filename' => $filename,
                    'file_path' => $filePath,
                    'file_size' => strlen($config),
                    'checksum' => hash('sha256', $config),
                    'backup_type' => $backupType,
                    'status' => 'success',
                ]);

                $device->markOnline();
                $job->markSuccess("Backup created: {$filename}");

                $this->auditService->logAction(
                    action: AuditAction::BACKUP_CONFIG,
                    objectType: 'backup',
                    objectId: $backup->id,
                    description: "Configuration backup created for: {$device->hostname}"
                );
            } else {
                ConfigurationBackup::create([
                    'device_id' => $device->id,
                    'user_id' => auth()->id(),
                    'filename' => 'failed_' . now()->format('Y-m-d_His'),
                    'file_path' => '',
                    'backup_type' => $backupType,
                    'status' => 'failed',
                    'error_message' => $result['error'],
                ]);

                $job->markFailed($result['error']);
            }
        } catch (\Exception $e) {
            $job->markFailed($e->getMessage());
        }

        return $job;
    }

    /**
     * Refresh interfaces from a device.
     */
    public function refreshInterfaces(Device $device): AutomationJob
    {
        $job = $this->createJob($device, JobType::REFRESH_INTERFACES);
        $job->markRunning();

        try {
            $params = $this->apiClient->buildDeviceParams($device);
            $result = $this->apiClient->getInterfaces($params);

            if ($result['success']) {
                $interfaces = $result['data']['interfaces'] ?? [];

                // Replace all interfaces for this device
                $device->interfaces()->delete();

                foreach ($interfaces as $iface) {
                    DeviceInterface::create([
                        'device_id' => $device->id,
                        'name' => $iface['name'],
                        'description' => $iface['description'] ?? null,
                        'ip_address' => $iface['ip_address'] ?? null,
                        'subnet_mask' => $iface['subnet_mask'] ?? null,
                        'mac_address' => $iface['mac_address'] ?? null,
                        'speed' => $iface['speed'] ?? null,
                        'duplex' => $iface['duplex'] ?? null,
                        'admin_status' => $iface['admin_status'] ?? 'down',
                        'oper_status' => $iface['oper_status'] ?? 'down',
                        'type' => $iface['type'] ?? null,
                        'raw_data' => $iface,
                    ]);
                }

                $device->markOnline();
                $job->markSuccess("Found " . count($interfaces) . " interfaces");
            } else {
                $job->markFailed($result['error']);
            }
        } catch (\Exception $e) {
            $job->markFailed($e->getMessage());
        }

        return $job;
    }

    /**
     * Create an automation job record.
     */
    protected function createJob(Device $device, JobType $type): AutomationJob
    {
        return AutomationJob::create([
            'user_id' => auth()->id(),
            'device_id' => $device->id,
            'job_type' => $type->value,
            'status' => JobStatus::PENDING,
        ]);
    }
}
