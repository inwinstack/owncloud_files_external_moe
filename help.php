<?php
namespace OCA\Files_External_MOE;

use OCP\Defaults;
use OCP\User;
use OCP\Util as CoreUtil;
use OCP\Template;

// Check if we are a user
User::checkLoggedIn();

$appManager = \OC::$server->getAppManager();
$config = \OC::$server->getConfig();
$defaults = new Defaults();

$tmpl = new Template('files_external_moe', 'help', '');
$tmpl->printPage();
