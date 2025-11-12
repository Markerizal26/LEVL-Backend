<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });

            return;
        }

        Schema::table('failed_jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('failed_jobs', 'uuid')) {
                $table->string('uuid')->unique()->after('id');
            }

            if (! Schema::hasColumn('failed_jobs', 'connection')) {
                $table->text('connection')->after('uuid');
            }

            if (! Schema::hasColumn('failed_jobs', 'queue')) {
                $table->text('queue')->after('connection');
            }

            if (! Schema::hasColumn('failed_jobs', 'payload')) {
                $table->longText('payload')->after('queue');
            }

            if (! Schema::hasColumn('failed_jobs', 'exception')) {
                $table->longText('exception')->after('payload');
            }

            if (! Schema::hasColumn('failed_jobs', 'failed_at')) {
                $table->timestamp('failed_at')->useCurrent()->after('exception');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
    }
};
