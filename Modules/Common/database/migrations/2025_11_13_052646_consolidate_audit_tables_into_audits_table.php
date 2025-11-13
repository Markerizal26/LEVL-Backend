<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->enum('action', [
                'create', 'update', 'delete', 'login', 'logout', 
                'assign', 'revoke', 'export', 'import', 'access', 
                'error', 'system'
            ])->default('system');
            
            $table->string('actor_type')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->string('target_table', 100)->nullable();
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            
            $table->string('module', 100)->nullable();
            $table->enum('context', ['system', 'application'])->default('application');
            
            $table->string('ip_address', 50)->nullable();
            $table->string('user_agent', 255)->nullable();
            
            $table->json('meta')->nullable();
            $table->json('properties')->nullable();
            
            $table->timestamp('logged_at')->useCurrent();
            $table->timestamps();

            $table->index(['action', 'logged_at']);
            $table->index(['user_id', 'module', 'action']);
            $table->index(['target_table', 'target_id']);
            $table->index(['target_type', 'target_id']);
            $table->index(['context', 'logged_at']);
        });

        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('system_audits');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('event', [
                'create', 'update', 'delete', 'login', 'logout', 'assign', 'revoke', 'export', 'import', 'system'
            ])->default('system');
            $table->string('target_type')->nullable(); 
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('actor_type')->nullable();  
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('properties')->nullable();
            $table->timestamp('logged_at')->useCurrent();
            $table->timestamps();

            $table->index(['event', 'logged_at']);
            $table->index(['target_type', 'target_id']);
        });

        Schema::create('system_audits', function (Blueprint $table) {
            $table->id();
            $table->enum('action', ['create', 'update', 'delete', 'access', 'export', 'import', 'error'])->default('access');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('module', 100)->nullable(); 
            $table->string('target_table', 100)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('ip_address', 50)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('meta')->nullable(); 
            $table->timestamp('logged_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'module', 'action']);
        });

        Schema::dropIfExists('audits');
    }
};
