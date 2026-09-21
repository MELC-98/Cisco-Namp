<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_facts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('hostname')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('os_version')->nullable();
            $table->string('uptime')->nullable();
            $table->string('hardware')->nullable();
            $table->string('image_file')->nullable();
            $table->json('raw_data')->nullable();      // Full parsed output
            $table->timestamp('discovered_at');
            $table->timestamps();

            $table->index('device_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_facts');
    }
};
