<?php

return [
	// User's initial points
	'initial_points' => 30000,

	// User's bet limit
	'limit_bet_points' => 100000,

	// Dummy bet points per win pattern (total pool = this * starter count).
	// Distributed by past average finish weights at create/edit.
	'dummy_points' => 10000,

	// Dummy bet points per quinella pattern (total pool = this * combination count)
	'dummy_quinella_points' => 3000,

	// Dummy bet points per exacta pattern (total pool = this * permutation count)
	'dummy_exacta_points' => 4000,

	// Exponent for dummy weight from past average finish.
	// score = (1 / avg_rank) ^ power. 1.0 = linear; higher = sharper favorites.
	'dummy_weight_power' => 1.5,

	// Display count of past games
	'past_game_count' => 10,

	// Calculate odds on an user's request
	'calc_odds_on_request' => false,

	// Interval of calculate odds, if 'calc_odds_on_request' is true
	'interval_calc_odds' => '5 min',

	// Confirm robot checkbox when Turnstile keys are not configured
	'confirm_robot' => false,

	// Cloudflare Turnstile (empty keys disable verification).
	// When set, first-visit user creation requires Turnstile.
	'turnstile_site_key' => env('TURNSTILE_SITE_KEY', ''),
	'turnstile_secret_key' => env('TURNSTILE_SECRET_KEY', ''),

	// Cookie expire minutes
	'cookie_expires' => 60*24*365,
];
