<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Bet;

class User extends Model
{
    use HasFactory;

    /**
     *  Check whether an ID is exists.
     */
    public static function exists_user($iden)
    {
        $record = User::where('personal_id', $iden)->take(1)->get();
        return count($record) > 0;
    }

    /**
     *  Regiter new user
     */
    public static function register_user($iden, $points)
    {
        $new_user = new User;
        $new_user->name = '';
        $new_user->personal_id = $iden;
        $new_user->points = $points;
        $new_user->admin = 0;
        return $new_user->save();
    }

    /**
     *  Get current points left
     */
    public function get_current_points()
    {
        $total_bet = DB::table('bets')->where('user_id', $this->id)->where('payed', 0)->sum('points');
        return $this->points - $total_bet;
    }
}
