<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::connection(config("activitylog.database_connection"))->table(
      config("activitylog.table_name"),
      function (Blueprint $table) {
        $table->string("ip_address")->nullable()->after("properties");
        $table->string("browser")->nullable()->after("ip_address");
        $table->string("browser_version")->nullable()->after("browser");
        $table->string("platform")->nullable()->after("browser_version");
        $table->string("device")->nullable()->after("platform");
        $table->string("device_type")->nullable()->after("device"); // mobile, tablet, desktop

        $table->index("ip_address");
        $table->index("device_type");
      },
    );
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::connection(config("activitylog.database_connection"))->table(
      config("activitylog.table_name"),
      function (Blueprint $table) {
        $table->dropIndex(["ip_address"]);
        $table->dropIndex(["device_type"]);

        $table->dropColumn([
          "ip_address",
          "browser",
          "browser_version",
          "platform",
          "device",
          "device_type",
        ]);
      },
    );
  }
};
