<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit logs are immutable from the application perspective.
     * No UPDATE or DELETE operations should ever be performed on this table.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action');                  // e.g. 'CREATE_USER', 'LOGIN', 'BACKUP_CONFIG'
            $table->string('object_type')->nullable(); // e.g. 'user', 'device', 'backup'
            $table->unsignedBigInteger('object_id')->nullable();
            $table->text('description')->nullable();
            $table->string('source_ip')->nullable();
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->json('metadata')->nullable();      // Extra context data
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
