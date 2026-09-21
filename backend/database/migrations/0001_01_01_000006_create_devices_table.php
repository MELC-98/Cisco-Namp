<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('hostname');
            $table->string('management_ip');
            $table->integer('ssh_port')->default(22);
            $table->string('vendor')->default('Cisco');
            $table->enum('category', ['router', 'switch']);
            $table->string('device_type');               // e.g. 'cisco_ios', 'cisco_xe'
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('os_name')->nullable();        // e.g. 'IOS', 'IOS XE'
            $table->string('os_version')->nullable();
            $table->foreignId('site_id')->nullable()->constrained()->onDelete('set null');
            $table->text('description')->nullable();
            $table->enum('status', ['online', 'offline', 'warning', 'unknown'])->default('unknown');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['management_ip', 'ssh_port']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
