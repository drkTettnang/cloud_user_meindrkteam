<?php

namespace OCA\User_MeinDRKTeam\MeinDRKTeam;

interface IRest
{
	/**
	 * Get user information from REST API.
	 *
	 * @param {string} $username username
	 * @param {string} $password password
	 * @return {array|false} Return user information if credentials are valid, false otherwise
	 */
	public function login($username, $password);
}
