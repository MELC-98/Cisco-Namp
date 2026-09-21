<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('edit_devices');
    }

    public function rules(): array
    {
        return [
            'hostname' => ['sometimes', 'string', 'max:255'],
            'management_ip' => ['sometimes', 'ip'],
            'ssh_port' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'category' => ['sometimes', 'in:router,switch'],
            'device_type' => ['sometimes', 'string', 'in:cisco_ios,cisco_xe'],
            'site_id' => ['nullable', 'exists:sites,id'],
            'description' => ['nullable', 'string', 'max:500'],

            // Credentials (optional on update)
            'username' => ['sometimes', 'string', 'max:255'],
            'password' => ['sometimes', 'string'],
            'enable_secret' => ['nullable', 'string'],
        ];
    }
}
