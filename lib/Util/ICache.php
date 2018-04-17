<?php

namespace OCA\User_MeinDRKTeam\Util;

interface ICache
{
	public function getUid($username);

	public function setUid($username, $uid);
}
