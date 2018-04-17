<?php

namespace OCA\User_MeinDRKTeam\Tests\User;

use OC\User\Backend;
use OCP\IUserBackend;
use OCP\ILogger;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\IUser;
use OCP\IGroupManager;
use OCP\IGroup;
use OCP\UserInterface;
use OCA\User_MeinDRKTeam\MeinDRKTeam\IRest;
use OCA\User_MeinDRKTeam\Util\ICache;
use OCA\User_MeinDRKTeam\User\MeinDRKTeam;
use PHPUnit\Framework\TestCase;

interface IBackend extends IUserBackend, UserInterface
{
	public function createUser($uid, $password);
	public function setPassword($uid, $password);
	public function checkPassword($uid, $password);
	public function setDisplayName($uid, $displayName);
}

class MeinDRKTeamTest extends TestCase
{
	private $realBackend;
	private $cache;
	private $logger;
	private $config;
	private $userManager;
	private $groupManager;
	private $rest;
	private $meinDRKTeam;

	public function setUp()
	{
		parent::setUp();

		$this->realBackend = $this->createMock(IBackend::class);
		$this->cache = $this->createMock(ICache::class);
		$this->logger = $this->createMock(ILogger::class);
		$this->config = $this->createMock(IConfig::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->rest = $this->createMock(IRest::class);

		$this->meinDRKTeam = new MeinDRKTeam(
			$this->realBackend,
			$this->cache,
			$this->logger,
			$this->config,
			$this->userManager,
			$this->groupManager,
			$this->rest
		);
	}

	public function testImplementsActions()
	{
		$this->assertTrue($this->meinDRKTeam->implementsActions(Backend::CHECK_PASSWORD));
		$this->assertTrue($this->meinDRKTeam->implementsActions(Backend::GET_HOME));
		$this->assertTrue($this->meinDRKTeam->implementsActions(Backend::GET_DISPLAYNAME));
		$this->assertTrue($this->meinDRKTeam->implementsActions(Backend::COUNT_USERS));
	}

	public function testCheckValidPasswordNotCachedAndNotExists()
	{
		$this->cache
		 ->expects($this->once())
		 ->method('getUid')
		 ->with('dummy_username')
		 ->willReturn(false);
		$this->rest
		 ->expects($this->once())
		 ->method('login')
		 ->with('dummy_username', 'dummy_password')
		 ->willReturn([
			'user' => [
				'id' => 'dummy_uid',
				'vorname' => 'dummy_first',
				'nachname' => 'dummy_last',
				'praeferierteEmail' => 'dummy@email',
				'praeferiertesTelefonMobil' => '0123456'
			]
		 ]);
		$this->realBackend
		 ->expects($this->once())
		 ->method('userExists')
		 ->with('dummy_uid')
		 ->willReturn(false);
		$this->realBackend
		 ->expects($this->once())
		 ->method('createUser')
		 ->with('dummy_uid', 'dummy_password')
		 ->willReturn(true);
		$this->cache
		 ->expects($this->once())
		 ->method('setUid')
		 ->with('dummy_username', 'dummy_uid');
		$this->realBackend
		 ->expects($this->once())
		 ->method('setDisplayName')
		 ->with('dummy_uid', 'dummy_first dummy_last');
		$this->config
		 ->expects($this->exactly(2))
		 ->method('setUserValue');

		$result = $this->meinDRKTeam->checkPassword('dummy_username', 'dummy_password');

		$this->assertEquals('dummy_uid', $result);
	}

	public function testCheckValidPasswordCached()
	{
		$this->cache
		 ->expects($this->once())
		 ->method('getUid')
		 ->with('dummy_username')
		 ->willReturn('dummy_uid');
		$this->realBackend
		 ->expects($this->once())
		 ->method('checkPassword')
		 ->with('dummy_uid', 'dummy_password')
		 ->willReturn(true);
		$this->config
		 ->expects($this->once())
		 ->method('getUserValue')
		 ->with('dummy_uid', 'login', 'lastLogin', 0)
		 ->willReturn(time());

		$result = $this->meinDRKTeam->checkPassword('dummy_username', 'dummy_password');

		$this->assertEquals('dummy_uid', $result);
	}

	public function testCheckInvalidPasswordNotCached()
	{
		$this->cache
		 ->expects($this->once())
		 ->method('getUid')
		 ->with('dummy_username')
		 ->willReturn(false);
		$this->rest
		 ->expects($this->once())
		 ->method('login')
		 ->with('dummy_username', 'dummy_password')
		 ->willReturn(false);

		$result = $this->meinDRKTeam->checkPassword('dummy_username', 'dummy_password');

		$this->assertFalse($result);
	}

	public function testCheckInvalidPasswordCached()
	{
		$this->cache
		 ->expects($this->once())
		 ->method('getUid')
		 ->with('dummy_username')
		 ->willReturn('dummy_uid');
		$this->realBackend
		 ->expects($this->once())
		 ->method('checkPassword')
		 ->with('dummy_uid', 'dummy_password')
		 ->willReturn(false);
		$this->config
		 ->expects($this->once())
		 ->method('getUserValue')
		 ->with('dummy_uid', 'login', 'lastLogin', 0)
		 ->willReturn(time());
		$this->rest
		 ->expects($this->once())
		 ->method('login')
		 ->with('dummy_username', 'dummy_password')
		 ->willReturn(false);

		$result = $this->meinDRKTeam->checkPassword('dummy_username', 'dummy_password');

		$this->assertFalse($result);
	}
}
