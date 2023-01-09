<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bet extends Model
{
    use HasFactory;

    const TYPE_WIN      = 0;
    const TYPE_QUINELLA = 1;
    const TYPE_EXACTA   = 2;
    const TYPE_TRIO     = 3;
    const TYPE_TIERCE   = 4;
    const TYPE_PLACE    = 5;
}
