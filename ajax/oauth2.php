<?php
/**
 * @author Adam Williamson <awilliam@redhat.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Volkan Gezer <volkangezer@gmail.com>
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

set_include_path(get_include_path().PATH_SEPARATOR.
	\OC_App::getAppPath('files_external_moe').'/3rdparty/google-api-php-client/src');

set_include_path(get_include_path().PATH_SEPARATOR.
\OC_App::getAppPath('files_external_moe').'/3rdparty');

require_once 'Google/Client.php';
require_once 'onedrive-php-sdk/vendor/autoload.php';
require_once 'dropbox-php-sdk/vendor/autoload.php';

OCP\JSON::checkAppEnabled('files_external_moe');
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();
$l = \OC::$server->getL10N('files_external_moe');

if (isset($_POST['client_type']) && $_POST['client_type'] == 'onedrive'){
    if (isset($_POST['client_id']) && isset($_POST['client_secret']) && isset($_POST['redirect'])) {

        if (isset($_POST['step'])) {
            $step = $_POST['step'];
            if ($step == 1) {
                $onedrive = new \Krizalys\Onedrive\Client(array(
                        'client_id' => $_POST['client_id']
                ));

                $url = $onedrive->getLogInUrl(array(
                        'wl.offline_access',
                        'wl.signin',
                        'wl.basic',
                        'wl.contacts_skydrive',
                        'wl.skydrive_update'
                ), $_POST['redirect']);

                $client_state = $onedrive->getState();

                    OCP\JSON::success(array('data' => array(
                    'url' => $url,
                    'state' => json_encode($client_state)
                    )));
            }

            else if ($step == 2 && isset($_POST['code'])) {
                $onedrive = new \Krizalys\Onedrive\Client(array(
                        'client_id' => $_POST['client_id'],
                        'state'     => json_decode($_POST['state'])
                ));
                $onedrive->obtainAccessToken($_POST['client_secret'], $_POST['code']);
                $client_state = $onedrive->getState();

                if (isset($client_state->token)){
                    OCP\JSON::success(array('data' => array(
                    'token' => true,
                    'state' => json_encode($client_state)
                    )));
                }
                else{
                    OCP\JSON::error(array('data' => array(
                    'message' => $l->t('Step 2 failed. Exception: %s', array('Access Token Not Access.'))
                    )));
                }
            }

         }
    }
}
else if(isset($_POST['client_type']) && $_POST['client_type'] == 'dropbox2'){
    if (isset($_POST['client_id']) && isset($_POST['client_secret']) && isset($_POST['redirect'])) {
        if (isset($_POST['step'])) {
            $step = $_POST['step'];
            if ($step == 1) {
                
                try{
                    $dropboxApp = new \Kunnu\Dropbox\DropboxApp($_POST['client_id'], $_POST['client_secret']);
                    
                    $dropbox = new \Kunnu\Dropbox\Dropbox($dropboxApp);
                    $authHelper = $dropbox->getAuthHelper();
                    $callbackUrl = $_POST['redirect'];
                    
                    $url = $authHelper->getAuthUrl($callbackUrl);
            
                    OCP\JSON::success(array('data' => array(
                    'url' => $url,
                    )));
                }
                catch(Exception $exception){
                    OCP\JSON::error(array('data' => array(
                    'message' => $l->t('Step 1 failed. Exception: %s', array($exception->getMessage()))
                    )));
                    }
            }
            else if ($step == 2 && isset($_POST['code']) && isset($_POST['state'])) {
                try {
                    $dropboxApp = new \Kunnu\Dropbox\DropboxApp($_POST['client_id'], $_POST['client_secret']);
                    $dropbox = new \Kunnu\Dropbox\Dropbox($dropboxApp);
                    $authHelper = $dropbox->getAuthHelper();
                    
                    $callbackUrl = $_POST['redirect'];
                    if (substr($_POST['code'],-1) == '#'){
                       $code = substr($_POST['code'],0,-1);
                    }
                    else {$code = $_POST['code'];}
                    $accessToken = $authHelper->getAccessToken($code, $_POST['state'], $callbackUrl);
                    $token = $accessToken->getToken();
                    OCP\JSON::success(array('data' => array(
                    'token' => $token,
                    )));
                }
                catch(Exception $exception){
                    OCP\JSON::error(array('data' => array(
                    'message' => $l->t('Step 2 failed. Exception: %s', array('Access Token Not Access.'))
                    )));
                }

            }
        }
    }
}
else{
    if (isset($_POST['client_id']) && isset($_POST['client_secret']) && isset($_POST['redirect'])) {
        $client = new Google_Client();
        $client->setClientId((string)$_POST['client_id']);
        $client->setClientSecret((string)$_POST['client_secret']);
        $client->setRedirectUri((string)$_POST['redirect']);
        $client->setScopes(array('https://www.googleapis.com/auth/drive'));
        $client->setApprovalPrompt('force');
        $client->setAccessType('offline');
        if (isset($_POST['step'])) {
            $step = $_POST['step'];
            if ($step == 1) {
                try {
                    $authUrl = $client->createAuthUrl();
                    OCP\JSON::success(array('data' => array(
                        'url' => $authUrl
                    )));
                } catch (Exception $exception) {
                    OCP\JSON::error(array('data' => array(
                        'message' => $l->t('Step 1 failed. Exception: %s', array($exception->getMessage()))
                    )));
                }
            } else if ($step == 2 && isset($_POST['code'])) {
                try {
                    $token = $client->authenticate((string)$_POST['code']);
                    OCP\JSON::success(array('data' => array(
                        'token' => $token
                    )));
                } catch (Exception $exception) {
                    OCP\JSON::error(array('data' => array(
                        'message' => $l->t('Step 2 failed. Exception: %s', array($exception->getMessage()))
                    )));
                }
            }
        }
    }
}

