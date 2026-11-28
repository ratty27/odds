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
		Schema::table('bets', function (Blueprint $table) {
			$table->index(['game_id', 'type', 'candidate_id0', 'candidate_id1', 'candidate_id2'], 'bets_game_type_candidates_index');
			$table->index(['user_id', 'payed'], 'bets_user_payed_index');
			$table->index(['game_id', 'user_id', 'payed'], 'bets_game_user_payed_index');
		});

		Schema::table('odds', function (Blueprint $table) {
			$table->index(['game_id', 'type', 'candidate_id0', 'candidate_id1', 'candidate_id2'], 'odds_game_type_candidates_index');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('bets', function (Blueprint $table) {
			$table->dropIndex('bets_game_type_candidates_index');
			$table->dropIndex('bets_user_payed_index');
			$table->dropIndex('bets_game_user_payed_index');
		});

		Schema::table('odds', function (Blueprint $table) {
			$table->dropIndex('odds_game_type_candidates_index');
		});
	}
};
