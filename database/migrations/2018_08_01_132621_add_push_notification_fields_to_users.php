<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPushNotificationFieldsToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {            
            $table->string('app_api_token', 255)->after('reports')->nullable();
            $table->tinyInteger('device_platform')->after('app_api_token')->comment('0=>android, 1=>ios')->nullable();
            $table->string('android_push_ids', 255)->after('device_platform')->nullable();
            $table->string('ios_push_ids', 255)->after('android_push_ids')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('app_api_token');
            $table->dropColumn('device_platform');
            $table->dropColumn('android_push_ids');
            $table->dropColumn('ios_push_ids');
        });
    }
}
