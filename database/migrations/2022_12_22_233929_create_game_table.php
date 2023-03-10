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
        Schema::create('games', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 256);
            $table->text('comment');
            $table->timestamp('limit');
            $table->integer('user_id')->index();
            $table->integer('status')->default(0)->index();
            $table->unsignedInteger('enabled')->default(0xffffffff);
            $table->timestamp('next_update');
            $table->integer('exclusion_update');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('games');
    }
};
