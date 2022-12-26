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
        Schema::create('odds', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('game_id')->index();
            $table->integer('type');
            $table->integer('candidate_id0')->default(-1);
            $table->integer('candidate_id1')->default(-1);
            $table->integer('candidate_id2')->default(-1);
            $table->double('odds')->default(1.0);
            $table->integer('favorite')->default(0);
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
        Schema::dropIfExists('odds');
    }
};
