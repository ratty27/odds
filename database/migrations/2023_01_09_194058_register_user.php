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
            $table->string('temp')->nullable()->after('token');
            $table->boolean('authorized')->default(0)->after('temp');
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
