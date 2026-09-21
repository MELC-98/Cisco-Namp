<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfigureInterfaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('configure_devices');
    }

    public function rules(): array
    {
        return [
            'interface_name' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'ip'],
            'subnet_mask' => ['nullable', 'ip'],
            'admin_status' => ['required', 'in:up,down'],
        ];
    }
}
