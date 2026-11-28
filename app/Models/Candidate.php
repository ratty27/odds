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
	 *	Pick readable text color for a frame background hex color.
	 *
	 *	@param	string|null	$hex	#RRGGBB
	 *	@return	string				#111111 or #ffffff
	 */
	public static function contrast_text_color($hex)
	{
		$hex = ltrim((string)$hex, '#');
		if( strlen($hex) != 6 )
		{
			return '#111111';
		}
		$r = hexdec(substr($hex, 0, 2));
		$g = hexdec(substr($hex, 2, 2));
		$b = hexdec(substr($hex, 4, 2));
		$yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
		return ($yiq >= 140) ? '#111111' : '#ffffff';
	}

	/**
	 *	Solid frame colors. First 8 follow JRA 枠色; next 8 are extra distinct solids.
	 *	Beyond 16 runners the palette repeats.
	 *
	 *	@return	array	list of #RRGGBB
	 */
	public static function frame_color_palette()
	{
		return [
			'#FFFFFF', // 1 white
			'#111111', // 2 black
			'#E60012', // 3 red
			'#0057A8', // 4 blue
			'#F5C400', // 5 yellow
			'#00A040', // 6 green
			'#F08200', // 7 orange
			'#E4007F', // 8 pink
			'#7A3E00', // 9 brown
			'#00A0C8', // 10 cyan
			'#6B2D8B', // 11 purple
			'#90EE90', // 12 light green
			'#C0C0C0', // 13 silver
			'#8B0000', // 14 dark red
			'#003366', // 15 navy
			'#FFDAB9', // 16 peach
		];
	}

	/**
	 *	Generate solid categorical frame colors by runner order.
	 *
	 *	@param	int	$count
	 *	@param	int	$seed	kept for call compatibility; unused
	 *	@return	array	list of #RRGGBB
	 */
	public static function generate_frame_colors($count, $seed = 0)
	{
		$colors = [];
		if( $count <= 0 )
		{
			return $colors;
		}
		$palette = self::frame_color_palette();
		$n = count($palette);
		for( $i = 0; $i < $count; ++$i )
		{
			$colors[] = $palette[$i % $n];
		}
		return $colors;
	}

	/**
	 *	Fill empty frame_color values in-memory for display.
	 *	Does not persist to DB.
	 *
	 *	@param	\Illuminate\Support\Collection	$candidates
	 *	@param	int								$seed
	 */
	public static function fill_missing_frame_colors($candidates, $seed)
	{
		$count = count($candidates);
		if( $count <= 0 )
		{
			return;
		}
		$needs_fill = false;
		foreach( $candidates as $candidate )
		{
			if( empty($candidate->frame_color) )
			{
				$needs_fill = true;
				break;
			}
		}
		if( !$needs_fill )
		{
			return;
		}
		$colors = self::generate_frame_colors($count, $seed);
		$i = 0;
		foreach( $candidates as $candidate )
		{
			if( empty($candidate->frame_color) )
			{
				$candidate->frame_color = $colors[$i];
			}
			++$i;
		}
	}

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
	 *	Races with exclude_history enabled are omitted.
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
			->where('games.exclude_history', 0)
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

	/**
	 *	Compute dummy bet weights from past average finishing positions.
	 *	weight ∝ 1 / avg(result_rank). Candidates with no history get the
	 *	average score of candidates that have history (or equal if none do).
	 *	Uses all finished races of the same organizer (exclude_history=0).
	 *
	 *	@param	int		$owner_user_id
	 *	@param	array	$names
	 *	@return	array	[ name => float weight ]  weights sum to 1.0
	 */
	public static function calc_dummy_weights($owner_user_id, $names)
	{
		$n = count($names);
		$weights = [];
		if( $n == 0 )
		{
			return $weights;
		}

		$equal = 1.0 / (float)$n;
		$past = self::get_past_results($owner_user_id, $names);
		$scores = [];
		$scored = [];
		foreach( $names as $name )
		{
			$history = $past[$name] ?? [];
			if( count($history) == 0 )
			{
				$scores[$name] = null;
				continue;
			}
			$sum_rank = 0.0;
			foreach( $history as $row )
			{
				$sum_rank += max(1, intval($row['result_rank']));
			}
			$avg_rank = $sum_rank / (float)count($history);
			$score = 1.0 / $avg_rank;
			$scores[$name] = $score;
			$scored[] = $score;
		}

		if( count($scored) == 0 )
		{
			foreach( $names as $name )
			{
				$weights[$name] = $equal;
			}
			return $weights;
		}

		$avg_score = array_sum($scored) / (float)count($scored);
		$total = 0.0;
		foreach( $names as $name )
		{
			if( $scores[$name] === null )
			{
				$scores[$name] = $avg_score;
			}
			$total += $scores[$name];
		}

		if( $total <= 0.0 )
		{
			foreach( $names as $name )
			{
				$weights[$name] = $equal;
			}
			return $weights;
		}

		foreach( $names as $name )
		{
			$weights[$name] = $scores[$name] / $total;
		}
		return $weights;
	}
}
