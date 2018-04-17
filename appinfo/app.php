<?php

use OCA\User_MeinDRKTeam\AppInfo\Application;

$app = new Application();
$container = $app->getContainer();

$userManager = \OC::$server->getUserManager();
$userManager->clearBackends();
$userManager->registerBackend($container->query('Proxy_Backend'));
