<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuration_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('job_id')->nullable()->constrained('automation_jobs')->onDelete('set null');
            $table->foreignId('backup_id')->nullable()->constrained('configuration_backups')->onDelete('set null');
            $table->string('change_type');             // e.g. 'interface_config'
            $table->text('configuration');              // The config commands applied
            $table->text('rollback_config')->nullable();
            $table->enum('status', ['pending', 'approved', 'deployed', 'failed', 'rolled_back'])->default('pending');
            $table->text('verification_result')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('deployed_at')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuration_changes');
    }
};
