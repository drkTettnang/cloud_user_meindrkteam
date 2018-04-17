<?php

namespace OCA\User_MeinDRKTeam\AppInfo;

use OCP\AppFramework\App;
use OCP\IContainer;
use OCA\User_MeinDRKTeam\Util\Cache;
use OCA\User_MeinDRKTeam\Util\LoggerProxy;
use OCA\User_MeinDRKTeam\User\Proxy;
use OCA\User_MeinDRKTeam\User\MeinDRKTeam;
use OCA\User_MeinDRKTeam\MeinDRKTeam\Rest;
use OCA\User_MeinDRKTeam\DataRetriever;
use OCA\User_MeinDRKTeam\Controller\SettingsController;
use OCA\User_MeinDRKTeam\Controller\ViewController;
use OC\User\Database;

class Application extends App
{
	private static $config = [];

	public function __construct(array $urlParams = [])
	{
		parent::__construct('user_meindrkteam', $urlParams);
		$container = $this->getContainer();

		$container->registerService('MeinDRKTeam_Cache', function (IContainer $c) {
			return new Cache(
				$c->query('OCP\IDBConnection')
			);
		});

		$container->registerService('MeinDRKTeam_Logger', function (IContainer $c) {
			return new LoggerProxy(
				$c->query('AppName'),
				$c->query('OCP\ILogger')
			);
		});

		$container->registerService('MeinDRKTeam_Rest', function (IContainer $c) {
			return new Rest(
				$c->query('MeinDRKTeam_Logger'),
				$c->query('OCP\IConfig'),
				$c->getServer()->getSession(),
				$c->query('MeinDRKTeam_DataRetriever')
			);
		});

		$container->registerService('Database_Backend', function (IContainer $c) {
			return new Database();
		});

		$container->registerService('MeinDRKTeam_Backend', function (IContainer $c) {
			return new MeinDRKTeam(
				$c->query('Database_Backend'),
				$c->query('MeinDRKTeam_Cache'),
				$c->query('MeinDRKTeam_Logger'),
				$c->query('OCP\IConfig'),
				$c->query('OCP\IUserManager'),
				$c->query('OCP\IGroupManager'),
				$c->query('MeinDRKTeam_Rest')
			);
		});

		$container->registerService('Proxy_Backend', function (IContainer $c) {
			return new Proxy(
				$c->query('MeinDRKTeam_Logger'),
				$c->query('Database_Backend'),
				$c->query('MeinDRKTeam_Backend')
			);
		});

		$container->registerService('MeinDRKTeam_DataRetriever', function (IContainer $c) {
			return new DataRetriever();
		});

		/**
		 * Controllers
		 */

		$container->registerService('ViewController', function (IContainer $c) {
			return new ViewController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('OCP\IConfig'),
				$c->getServer()->getSession()
			);
		});
	}
}
