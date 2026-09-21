<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('create_devices');
    }

    public function rules(): array
    {
        return [
            'hostname' => ['required', 'string', 'max:255'],
            'management_ip' => ['required', 'ip'],
            'ssh_port' => ['integer', 'min:1', 'max:65535'],
            'category' => ['required', 'in:router,switch'],
            'device_type' => ['required', 'string', 'in:cisco_ios,cisco_xe'],
            'site_id' => ['nullable', 'exists:sites,id'],
            'description' => ['nullable', 'string', 'max:500'],

            // Credentials
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'enable_secret' => ['nullable', 'string'],
        ];
    }
}
