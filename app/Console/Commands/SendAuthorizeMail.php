<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Mail\AuthorizeMail;

class SendAuthorizeMail extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'command:send-auth-mail';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Send authorize mails';

	/**
	 * Execute the console command.
	 *
	 * @return int
	 */
	public function handle()
	{
		DB::transaction(function ()
			{
				$users = User::where('authorized', 1)->select('id', 'personal_id', 'temp')
					->select('id', 'personal_id', 'email', 'temp', 'authorized')->get();
				foreach( $users as $user )
				{
					Mail::to($user->email)->send(new AuthorizeMail($user->personal_id, $user->temp));
					$user->authorized = 2;
					$user->update();
				}
			} );
		return Command::SUCCESS;
	}
}
