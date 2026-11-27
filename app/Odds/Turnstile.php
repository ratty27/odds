<?php

namespace App\Odds;

use Illuminate\Support\Facades\Http;

class Turnstile
{
	/**
	 *	Whether Turnstile verification is configured
	 */
	public static function is_enabled()
	{
		$site = (string)config('odds.turnstile_site_key');
		$secret = (string)config('odds.turnstile_secret_key');
		return $site !== '' && $secret !== '';
	}

	/**
	 *	Verify a Turnstile response token
	 *
	 *	@return	bool
	 */
	public static function verify($token, $remote_ip = null)
	{
		if( !self::is_enabled() )
		{
			return true;
		}

		$token = trim((string)$token);
		if( $token === '' )
		{
			return false;
		}

		$payload = [
			'secret' => config('odds.turnstile_secret_key'),
			'response' => $token,
		];
		if( !is_null($remote_ip) && $remote_ip !== '' )
		{
			$payload['remoteip'] = $remote_ip;
		}

		try
		{
			$response = Http::asForm()
				->timeout(5)
				->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', $payload);
		}
		catch( \Throwable $e )
		{
			return false;
		}

		if( !$response->ok() )
		{
			return false;
		}

		$data = $response->json();
		return is_array($data) && !empty($data['success']);
	}
}
