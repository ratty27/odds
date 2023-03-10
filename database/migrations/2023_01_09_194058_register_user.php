<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->after('personal_id')->index();
            $table->string('token')->nullable()->after('email');
            $table->string('temp')->nullable()->after('token')->index();
            $table->timestamp('temp_limit')->after('temp');

            // 0=Not authorized / 1=wait for send mail / 2=wait for user confirming / 3=authorized
            $table->tinyInteger('authorized')->index()->default(0)->after('temp');
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
            $table->dropColumn('authorized');
            $table->dropColumn('temp');
            $table->dropColumn('token');
            $table->dropColumn('email');
        });
    }
};
