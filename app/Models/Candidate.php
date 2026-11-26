<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Bet;
use App\Models\Odd;

class Candidate extends Model
{
	use HasFactory;

	/**
	 *	Delete this candidate
	 */
	public function safe_delete()
	{
		Odd::where('candidate_id0', $this->id)
		 ->orWhere('candidate_id1', $this->id)
		 ->orWhere('candidate_id2', $this->id)
		 ->delete();
		Bet::where('candidate_id0', $this->id)
		 ->orWhere('candidate_id1', $this->id)
		 ->orWhere('candidate_id2', $this->id)
		 ->delete();
		$this->delete();
	}

	/**
	 *	Get race results for candidate names under the same organizer.
	 *	Includes finished races (status=2) that have ranks, including the current race.
	 *	Results are keyed by candidate name.
	 *
	 *	@param	int		$owner_user_id		Game organizer user id
	 *	@param	array	$names				Candidate names to look up
	 *	@return	array	[ name => [ ['game_name' => string, 'result_rank' => int], ... ], ... ]
	 */
	public static function get_past_results($owner_user_id, $names)
	{
		$result = [];
		if( count($names) == 0 )
		{
			return $result;
		}

		$rows = DB::table('candidates')
			->join('games', 'candidates.game_id', '=', 'games.id')
			->where('games.user_id', $owner_user_id)
			->where('games.status', 2)
			->whereIn('candidates.name', $names)
			->where('candidates.result_rank', '>=', 0)
			->orderBy('games.id', 'desc')
			->select('candidates.name', 'games.name as game_name', 'candidates.result_rank')
			->get();

		foreach( $rows as $row )
		{
			if( !isset($result[$row->name]) )
			{
				$result[$row->name] = [];
			}
			$result[$row->name][] = [
				'game_name' => $row->game_name,
				'result_rank' => $row->result_rank,
			];
		}

		return $result;
	}
}
