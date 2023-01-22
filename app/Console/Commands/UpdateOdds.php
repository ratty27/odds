<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Game;

class UpdateOdds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:update-odds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-calculate odds immediately';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $games = Game::where('status', '0')->get();
        foreach( $games as $game )
        {
            DB::transaction(
                function () use($game)
                {
                    $game->update_odds();
                }
            );
        }
        return Command::SUCCESS;
    }
}
