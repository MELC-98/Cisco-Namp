<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_interfaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('name');                    // e.g. 'GigabitEthernet0/0/0'
            $table->text('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('subnet_mask')->nullable();
            $table->string('mac_address')->nullable();
            $table->string('speed')->nullable();
            $table->string('duplex')->nullable();
            $table->enum('admin_status', ['up', 'down'])->default('up');
            $table->enum('oper_status', ['up', 'down'])->default('down');
            $table->string('type')->nullable();        // e.g. 'ethernet', 'loopback', 'vlan'
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->unique(['device_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_interfaces');
    }
};
