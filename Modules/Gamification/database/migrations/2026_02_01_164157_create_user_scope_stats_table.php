<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('user_scope_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('scope_type'); 
            $table->unsignedBigInteger('scope_id');
            $table->unique(['user_id', 'scope_type', 'scope_id']);
            $table->unsignedBigInteger('total_xp')->default(0);
            $table->integer('current_level')->default(1);
            $table->timestamps();

            $table->index(['scope_type', 'scope_id', 'total_xp']); 
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('user_scope_stats');
    }
};
