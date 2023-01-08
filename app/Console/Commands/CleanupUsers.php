<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CleanupUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:cleanup-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup no-bet users';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        User::CleanupUsers();
        return Command::SUCCESS;
    }
}
