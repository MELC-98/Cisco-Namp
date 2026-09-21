<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Device credentials stored in a separate table for security isolation.
     * Passwords are encrypted using Laravel's Crypt facade (AES-256-CBC).
     * This table is designed so a HashiCorp Vault adapter can replace
     * the encryption layer without schema changes.
     */
    public function up(): void
    {
        Schema::create('device_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('username');
            $table->text('password');              // Encrypted at application level
            $table->text('enable_secret')->nullable(); // Encrypted at application level
            $table->timestamps();

            $table->unique('device_id'); // One credential set per device
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_credentials');
    }
};
