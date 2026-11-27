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
				$users = User::whereIn('authorized', [
						User::AUTHORIZED_WAIT_SEND,
						User::AUTHORIZED_WAIT_CONFIRM,
						User::AUTHORIZED_OK,
					])
					->select('id', 'personal_id', 'email', 'temp', 'authorized', 'name')
					->get();

				foreach( $users as $user )
				{
					if( User::is_suspicious_name($user->name) )
					{
						$user->ban();
						continue;
					}

					if( (int)$user->authorized !== User::AUTHORIZED_WAIT_SEND )
					{
						continue;
					}

					Mail::to($user->email)->send(new AuthorizeMail($user->personal_id, $user->temp));
					$user->authorized = User::AUTHORIZED_WAIT_CONFIRM;
					$user->update();
				}
			} );
		return Command::SUCCESS;
	}
}
