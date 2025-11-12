<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('otp_codes')) {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('channel', ['email']); 
            $table->string('provider', 50)->nullable();

            $table->enum('purpose', [
                'register_verification',    
                'password_reset',           
                'email_change_verification',
                'two_factor_auth',          
            ]);

            $table->string('code', 20);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'purpose', 'expires_at']);
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
