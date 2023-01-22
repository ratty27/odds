<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
}
