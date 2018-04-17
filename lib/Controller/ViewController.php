<?php
namespace OCA\User_MeinDRKTeam\Controller;

use OCP\IRequest;
use OCP\IConfig;
use OCP\ISession;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Controller;

class ViewController extends Controller
{
	private $config;
	private $session;
	
	public function __construct(
		$appName,
		IRequest $request,
		IConfig $config,
		ISession $session
	
	) {
		parent::__construct($appName, $request);
		
		$this->config = $config;
		$this->session = $session;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index()
	{
		$token = '';
		$url = 'https://meindrk.team';

		$csp = new ContentSecurityPolicy();
		$csp->addAllowedFrameDomain($url);
		
		$response = new TemplateResponse('user_meindrkteam', 'view/index', ['url' => $url]);
		$response->setContentSecurityPolicy($csp);

		return $response;
	}
}
