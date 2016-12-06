<?php
/**
 * @author Christian Berendt <berendt@b1-systems.de>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author j-ed <juergen@eisfair.org>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Ross Nicoll <jrn@jrn.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 * @author Duncan Chiang <duncan.c@inwinstack.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
if(OC_App::isEnabled('files_external')) {
    OC_App::cleanAppId('files_external');
    OC_App::disable('files_external');
}

OC::$CLASSPATH['OC\Files\Storage\StreamWrapper'] = 'files_external_moe/lib/streamwrapper.php';
OC::$CLASSPATH['OC\Files\Storage\Google'] = 'files_external_moe/lib/google.php';
OC::$CLASSPATH['OC_Mount_Config'] = 'files_external_moe/lib/config.php';
OC::$CLASSPATH['OCA\Files\External\Api'] = 'files_external_moe/lib/api.php';
OC::$CLASSPATH['OC\Files\Storage\OneDrive'] = 'files_external_moe/lib/onedrive.php';
OC::$CLASSPATH['OC\Files\Storage\Dropbox2'] = 'files_external_moe/lib/dropbox2.php';
require_once __DIR__ . '/../3rdparty/autoload.php';

// register Application object singleton
\OC_Mount_Config::$app = new \OCA\Files_External_MOE\Appinfo\Application();
$appContainer = \OC_Mount_Config::$app->getContainer();

$l = \OC::$server->getL10N('files_external_moe');

OCP\App::registerAdmin('files_external_moe', 'settings');
if (OCP\Config::getAppValue('files_external_moe', 'allow_user_mounting', 'yes') == 'yes') {
	OCP\App::registerPersonal('files_external_moe', 'personal');
}

\OCA\Files\App::getNavigationManager()->add([
	"id" => 'extstoragemounts',
	"appname" => 'files_external_moe',
	"script" => 'list.php',
	"order" => 30,
	"name" => $l->t('External storage')
]);

// connecting hooks
OCP\Util::connectHook('OC_Filesystem', 'post_initMountPoints', '\OC_Mount_Config', 'initMountPointsHook');

$mountProvider = $appContainer->query('OCA\Files_External_MOE\Config\ConfigAdapter');
\OC::$server->getMountProviderCollection()->registerProvider($mountProvider);

