<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Game;
use App\Models\Candidate;
use App\Models\Odd;
use App\Models\Bet;

class RuleTest extends TestCase
{
	/**
	 * Test basic flow
	 *
	 * @return void
	 */
	public function testBasicFlow()
	{
		$user_id = User::register_user(User::generate_token(), 10000);
		$this->assertTrue( $user_id >= 1 );

		$game_id = Game::new_game($user_id, 'TestGame', date("Y/m/d H:i:s"), '', 1, ['A', 'B', 'C', 'D', 'E'], 0);
		$this->assertTrue( $user_id >= 1 );

		$game = Game::find($game_id);
		$game->update_game('TestGame', date("Y/m/d H:i:s"), '', 1, ['A', 'C', 'B', 'D', 'F'], 0);

		$this->assertSame( Odd::where('game_id', $game_id)->count(), 5 );

		$candidates = Candidate::where('game_id', $game_id)->get();
		$this->assertSame( count($candidates), 5 );
		foreach( $candidates as $candidate )
		{
			if( $candidate->name == 'A' )
			{
				$this->assertSame( $candidate->disp_order, 0 );
			}
			else if( $candidate->name == 'C' )
			{
				$this->assertSame( $candidate->disp_order, 1 );
			}
			else if( $candidate->name == 'B' )
			{
				$this->assertSame( $candidate->disp_order, 2 );
			}
			else if( $candidate->name == 'D' )
			{
				$this->assertSame( $candidate->disp_order, 3 );
			}
			else if( $candidate->name == 'F' )
			{
				$this->assertSame( $candidate->disp_order, 4 );
			}
		}

		$user = User::find($user_id);
		$user->safe_delete();
	}

	/**
	 * Test the rule of win
	 *
	 * @return void
	 */
	public function testRuleWin()
	{
		$user_id = User::register_user(User::generate_token(), 10000);
		$this->assertTrue( $user_id >= 1 );

		$game_id = Game::new_game($user_id, 'TestGame', date("Y/m/d H:i:s"), '', 7, ['A', 'B', 'C', 'D', 'E'], 0);
		$this->assertTrue( $user_id >= 1 );

		$candidates = Candidate::where('game_id', $game_id)->get();
		$this->assertSame( count($candidates), 5 );

		// Bet
		$user_bet = [
			[ 'type' => Bet::TYPE_WIN, 'user_id' => $user_id, 'game_id' => $game_id, 'candidate_id0' => $candidates[1]->id, 'points' => 5000 ],
			[ 'type' => Bet::TYPE_WIN, 'user_id' => $user_id, 'game_id' => $game_id, 'candidate_id0' => $candidates[2]->id, 'points' => 5000 ],
		];
		Bet::insert( $user_bet );

		// Finish a game
		$candidates[1]->result_rank = 1;
		$candidates[1]->update();
		$candidates[2]->result_rank = 2;
		$candidates[2]->update();
		$game = Game::find($game_id);
		$game->finish();

		// Pay off
		$odds = Odd::where('type', Bet::TYPE_WIN)->where('candidate_id0', $candidates[1]->id)->first();
		$reward = (int)($odds->odds * 5000);
		$user = User::find($user_id);
		$user->ReceiveRewards();
		// Check whether the rewards are correct
		$user = User::find($user_id);
		$this->assertTrue( abs($user->points - $reward) < 2, 'points: ' . $user->points . ' / reward: ' . $reward );

		// finalize
		$user->safe_delete();
	}

	/**
	 * Test the rule of quinella
	 *
	 * @return void
	 */
	public function testRuleQuinella()
	{
		$user_id = User::register_user(User::generate_token(), 10000);
		$this->assertTrue( $user_id >= 1 );

		$game_id = Game::new_game($user_id, 'TestGame', date("Y/m/d H:i:s"), '', 7, ['A', 'B', 'C', 'D', 'E'], 0);
		$this->assertTrue( $user_id >= 1 );

		$candidates = Candidate::where('game_id', $game_id)->get();
		$this->assertSame( count($candidates), 5 );

		// Bet
		$user_bet = [
			[ 'type' => Bet::TYPE_QUINELLA, 'user_id' => $user_id, 'game_id' => $game_id, 'candidate_id0' => $candidates[1]->id, 'candidate_id1' => $candidates[2]->id, 'points' => 5000 ],
			[ 'type' => Bet::TYPE_QUINELLA, 'user_id' => $user_id, 'game_id' => $game_id, 'candidate_id0' => $candidates[0]->id, 'candidate_id1' => $candidates[1]->id, 'points' => 5000 ],
		];
		Bet::insert( $user_bet );

		// Finish a game
		$candidates[1]->result_rank = 1;
		$candidates[1]->update();
		$candidates[2]->result_rank = 2;
		$candidates[2]->update();
		$game = Game::find($game_id);
		$game->finish();

		// Pay off
		$odds = Odd::where('type', Bet::TYPE_QUINELLA)->where('candidate_id0', $candidates[1]->id)->where('candidate_id1', $candidates[2]->id)->first();
		$reward = (int)($odds->odds * 5000);
		$user = User::find($user_id);
		$user->ReceiveRewards();
		// Check whether the rewards are correct
		$user = User::find($user_id);
		$this->assertTrue( abs($user->points - $reward) < 2, 'points: ' . $user->points . ' / reward: ' . $reward );

		// finalize
		$user->safe_delete();
	}

	/**
	 * Test the rule of exacta
	 *
	 * @return void
	 */
	public function testRuleExacta()
	{
		$user_id = User::register_user(User::generate_token(), 10000);
		$this->assertTrue( $user_id >= 1 );

		$game_id = Game::new_game($user_id, 'TestGame', date("Y/m/d H:i:s"), '', 7, ['A', 'B', 'C', 'D', 'E'], 0);
		$this->assertTrue( $user_id >= 1 );

		$candidates = Candidate::where('game_id', $game_id)->get();
		$this->assertSame( count($candidates), 5 );

		// Bet
		$user_bet = [
			[ 'type' => Bet::TYPE_EXACTA, 'user_id' => $user_id, 'game_id' => $game_id, 'candidate_id0' => $candidates[1]->id, 'candidate_id1' => $candidates[2]->id, 'points' => 5000 ],
			[ 'type' => Bet::TYPE_EXACTA, 'user_id' => $user_id, 'game_id' => $game_id, 'candidate_id0' => $candidates[2]->id, 'candidate_id1' => $candidates[1]->id, 'points' => 5000 ],
		];
		Bet::insert( $user_bet );

		// Finish a game
		$candidates[1]->result_rank = 2;
		$candidates[1]->update();
		$candidates[2]->result_rank = 1;
		$candidates[2]->update();
		$game = Game::find($game_id);
		$game->finish();

		// Pay off
		$odds = Odd::where('type', Bet::TYPE_EXACTA)->where('candidate_id0', $candidates[2]->id)->where('candidate_id1', $candidates[1]->id)->first();
		$reward = (int)($odds->odds * 5000);
		$user = User::find($user_id);
		$user->ReceiveRewards();
		// Check whether the rewards are correct
		$user = User::find($user_id);
		$this->assertTrue( abs($user->points - $reward) < 2, 'points: ' . $user->points . ' / reward: ' . $reward );

		// finalize
		$user->safe_delete();
	}
}
