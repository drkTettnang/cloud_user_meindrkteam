<?php

namespace OCA\User_MeinDRKTeam\Tests\MeinDRKTeam;

use OCP\ILogger;
use OCP\IConfig;
use OCP\ISession;
use OCA\User_MeinDRKTeam\IDataRetriever;
use OCA\User_MeinDRKTeam\MeinDRKTeam\Rest;
use PHPUnit\Framework\TestCase;

class RestTest extends TestCase
{
	private $logger;
	private $config;
	private $session;
	private $dataRetriever;

	private $rest;

	public function setUp()
	{
		parent::setUp();

		$this->logger = $this->createMock(ILogger::class);
		$this->config = $this->createMock(IConfig::class);
		$this->session = $this->createMock(ISession::class);
		$this->dataRetriever = $this->createMock(IDataRetriever::class);

		$this->rest = new Rest(
			$this->logger,
			$this->config,
			$this->session,
			$this->dataRetriever
		);
	}

	public function testSuccessfulLogin()
	{
		$this->mockDataRetriever([
			'body' => '{"user":{"id":12345},"success":true,"sessionId":"654321FA"}'
		]);

		$result = $this->rest->login('dummy_user', 'dummy_password');

		$this->assertTrue($result['success']);
		$this->assertEquals(12345, $result['user']['id']);
		$this->assertEquals('654321FA', $result['sessionId']);
	}

	public function testUnavailable()
	{
		$this->mockDataRetriever([
			'body' => false
		]);
		$this->mockLogWarning('MeinDRK.team REST API not reachable.');

		$result = $this->rest->login('dummy_user', 'dummy_password');

		$this->assertFalse($result);
	}

	public function testInvalidCredentials()
	{
		$this->mockDataRetriever([
			'body' => '{"success":false,"reason":"wrong credentionals"}'
		]);
		$this->mockLogWarning('Login failed with reason: wrong credentionals');

		$result = $this->rest->login('dummy_user', 'dummy_password');

		$this->assertFalse($result);
	}

	public function testEmptyResponse()
	{
		$this->mockDataRetriever([
			'body' => ''
		]);
		$this->mockLogWarning('Could not decode response.');

		$result = $this->rest->login('dummy_user', 'dummy_password');

		$this->assertFalse($result);
	}

	public function testInvalidJSONResponse()
	{
		$this->mockDataRetriever([
			'body' => 'fooobar'
		]);
		$this->mockLogWarning('Could not decode response.');

		$result = $this->rest->login('dummy_user', 'dummy_password');

		$this->assertFalse($result);
	}

	private function mockDataRetriever($result = [])
	{
		$this->dataRetriever
			->expects($this->once())
			->method('fetchUrl')
			->with('https://meindrk.team/backend/rest/app/login', [
				'login' => 'dummy_user',
				'password' => 'dummy_password',
				'type' => 'helfer'
			])
			->willReturn($result);
	}

	private function mockLogWarning($message)
	{
		$this->logger
			->expects($this->once())
			->method('warning')
			->with($message);
	}

	private function mockLogInfo($message)
	{
		$this->logger
			->expects($this->once())
			->method('info')
			->with($message);
	}
}
