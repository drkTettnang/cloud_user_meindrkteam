<?php

namespace OCA\User_MeinDRKTeam\User;

use OC\User\Backend;
use OCP\IUserBackend;
use OCP\ILogger;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\IUser;
use OCP\IGroupManager;
use OCA\User_MeinDRKTeam\Util\ICache;

class MeinDRKTeam
{
	private static $userData = null;

	// How long should we try our cached username-uid?
	const TIMEOUT_CACHE = 86400; //24h

	private $realBackend;
	private $cache;
	private $logger;
	private $config;
	private $userManager;
	private $groupManager;
	private $restAPI;
	private $singleSO;

	public function __construct(
		$realBackend,
		ICache $cache,
		ILogger $logger,
		IConfig $config,
		IUserManager $userManager,
		IGroupManager $groupManager,
		$rest
	) {
		$this->realBackend = $realBackend;
		$this->cache = $cache;
		$this->logger = $logger;
		$this->config = $config;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->rest = $rest;
	}

	public function implementsActions($actions)
	{
		return (bool)((Backend::CHECK_PASSWORD
		| Backend::GET_HOME
		| Backend::GET_DISPLAYNAME
		// | Backend::PROVIDE_AVATAR
		| Backend::COUNT_USERS)
		& $actions);
	}

	public function checkPassword($username, $password)
	{
		$this->logger->debug('Use MeinDRK.team to check password.');

		$uid = $this->cache->getUid($username);

		// try cached credentials, if still valid
		if ($uid) {
			if ($this->isInTime($uid)) {
				if ($this->realBackend->checkPassword($uid, $password)) {
					$this->logger->info('Correct cached credentials for {username} ({uid}).', ['username' => $username, 'uid' => $uid]);

					return $uid;
				}
			}
		}

		$loginResponse = $this->rest->login($username, $password);

		if ($loginResponse === false) {
			return false;
		}

		$this->logger->info('Correct password for {username} ({uid}).', ['username' => $username, 'uid' => $loginResponse['user']['id']]);

		return $this->updateUser($loginResponse['user'], $username, $password);
	}

	private function updateUser($userinfo, $username, $password)
	{
		$uid = $userinfo ['id'];

		if (! $this->realBackend->userExists($uid)) {
			if ($this->realBackend->createUser($uid, $password)) {
				$this->logger->info('New user ({uid}) created.', ['uid' => $uid]);
			} else {
				$this->logger->warning('Could not create user ({uid}).', ['uid' => $uid]);

				return false;
			}
		} else {
			// update password
			if ($this->realBackend->setPassword($uid, $password)) {
				$this->logger->info("Password updated.");
			} else {
				$this->logger->info("Password update failed!");
			}
		}

		$this->cache->setUid($username, $uid);

		$this->realBackend->setDisplayName($uid, $userinfo ['vorname'] . ' ' . $userinfo ['nachname']);

		$this->config->setUserValue($uid, 'settings', 'email', $userinfo['praeferierteEmail']);
		$this->config->setUserValue($uid, 'settings', 'phone', $userinfo['praeferiertesTelefonMobil']);

		// $user = $this->userManager->get($uid);
		// if (!$this->isInTime($uid) || $this->config->getSystemValue('config') === 'true') {
		// 	// update group memberships
		// 	$this->syncGroupMemberships($user, $this->restAPI->getUserData($username, $password));
		// }

		return $uid;
	}

	/**
	* Check if we are allowed to use our cached username-uid.
	*
	* @param  {string}  $uid user id
	* @return boolean True if we are in our timeframe
	*/
	private function isInTime($uid)
	{
		$lastLogin = $this->config->getUserValue($uid, 'login', 'lastLogin', 0);

		return ($lastLogin + self::TIMEOUT_CACHE) > time();
	}
}
