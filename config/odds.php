<?php

return [
	// User's initial points
	'initial_points' => 30000,

	// User's bet limit
	'limit_bet_points' => 100000,

	// Dummy user's bet points
	'dummy_points' => 10000,

	// Dummy user's bet points for quinella
	'dummy_quinella_points' => 3000,

	// Dummy user's bet points for exacta
	'dummy_exacta_points' => 4000,

	// Display count of past games
	'past_game_count' => 10,

	// Calculate odds on an user's request
	'calc_odds_on_request' => false,

	// Interval of calculate odds, if 'calc_odds_on_request' is true
	'interval_calc_odds' => '5 min',

	// Confirm robot
	'confirm_robot' => true,
];
