<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Candidate;

class Game extends Model
{
    use HasFactory;

    /**
     *  Update game's odds
     */
    public function update_odds()
    {
        $game_id = $this->id;
        DB::transaction(function () use($game_id)
            {
            }
        );
    }

    /**
     *  Update game's odds
     */
    public function update_odds_if_needs()
    {
        if( $this->exclusion_update == 0 )
        {
            $current = time();
            $next = strtotime($this->next_update);
            if( $current >= $next )
            {
                $this->increment('exclusion_update');
                $this->save();
                if( $this->exclusion_update == 1 )
                {
                    $this->update_odds();
                }
            }
        }
    }
}
