<?php
/**
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
OCP\JSON::checkAppEnabled('files_external_moe');
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();
if (isset($_POST['external_path'])){
    $user = \OC_USER::getUser();
    $view = new \OC\Files\View("/".$user."/files");
    $result = '';
    //step1: check invalid path.
    try{
        $view->verifyPath('/',$_POST['external_path']);
    }catch(\OCP\Files\InvalidPathException $ex){
        $result = 'invalid';
    }
    //step2: check file is exist.
    if($result == '') {
        $result = $view->file_exists($_POST['external_path']);
    }
    
    OCP\JSON::success(array('data' => array(
                    'result' => $result,
                    )));

}
