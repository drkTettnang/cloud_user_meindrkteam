<?php

namespace OCA\User_MeinDRKTeam\MeinDRKTeam;

use OCP\ILogger;
use OCP\IConfig;
use OCP\ISession;
use OCA\User_MeinDRKTeam\IDataRetriever;

class Rest implements IRest
{
	const RESTURL = 'https://meindrk.team/backend/rest/app';

	private $logger;
	private $config;
	private $session;
	private $dataRetriever;

	public function __construct(
		ILogger $logger,
		IConfig $config,
		ISession $session,
		IDataRetriever $dataRetriever
	) {
		$this->logger = $logger;
		$this->config = $config;
		$this->session = $session;
		$this->dataRetriever = $dataRetriever;
	}

	public function login($username, $password)
	{
		$response = $this->dataRetriever->fetchUrl(self::RESTURL.'/login', [
			'login' => $username,
			'password' => $password,
			'type' => 'helfer',
		]);

		if ($response['body'] === false) {
			$this->logger->warning('MeinDRK.team REST API not reachable.');

			return false;
		}

		$jsonResponse = @json_decode($response['body'], true);

		if ($jsonResponse === false || $jsonResponse === null) {
			$this->logger->warning('Could not decode response.');

			return false;
		}

		if ($jsonResponse['success'] !== true) {
			$this->logger->warning('Login failed with reason: '.$jsonResponse['reason']);

			return false;
		}

		return $jsonResponse;
	}
}
