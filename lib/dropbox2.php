<?php
/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Philipp Kapfer <philipp.kapfer@gmx.at>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Sascha Schmidt <realriot@realriot.de>
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

namespace OC\Files\Storage;

use Icewind\Streams\IteratorDirectory;

set_include_path(get_include_path().PATH_SEPARATOR.
\OC_App::getAppPath('files_external_moe').'/3rdparty/');

require_once 'dropbox-php-sdk/vendor/autoload.php';
use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\DropboxFile;
use Kunnu\Dropbox\Exceptions\DropboxClientException;

class Dropbox2 extends \OC\Files\Storage\Common {

	private $dropbox;
	private $root;
	private $id;
	private $metaData = array();

	private static $tempFiles = array();

	public function __construct($params) {
	    if (isset($params['configured']) && $params['configured'] === 'true'
			&& isset($params['client_id']) && isset($params['client_secret'])
			&& isset($params['token']) && $params['token'] !== false
	    ) {
		    
			$this->root = isset($params['root']) ? $params['root'] : '/';
			
			$this->id = 'dropbox2::'.substr($params['token'], 0, 30);
			
			try{
    			$app = new DropboxApp($params['client_id'], $params['client_secret'], $params['token']);
    			
    			$this->dropbox = new Dropbox($app);
			}
			catch(\Exception $exception){
			    \OCP\Util::writeLog('dropbox2', 'lazy connection', \OCP\Util::ERROR);
			}
		} else {
			throw new \Exception('Creating \OC\Files\Storage\DropboxbyOauth2 storage failed');
		}
	}

	/**
	 * @param string $path
	 */
	private function deleteMetaData($path) {
		$path = $this->root.$path;
		if (isset($this->metaData[$path])) {
			unset($this->metaData[$path]);
			return true;
		}
		return false;
	}

	/**
	 * Returns the path's metadata
	 * @param string $path path for which to return the metadata
	 * @param bool $list if true, also return the directory's contents
	 * @return mixed directory contents if $list is true, file metadata if $list is
	 * false, null if the file doesn't exist or "false" if the operation failed
	 */
	private function getDropBoxMetaData($path, $list = false) {
		$path = $this->root.$path;
		
		if ( !$list && isset($this->metaData[$path])) {
			return $this->metaData[$path];
		} else {
			if ($list) {
				try {
					$response = $this->dropbox->listFolder($path);
				} catch (\Exception $exception) {
				    
				    \OCP\Util::writeLog('files_external_moe', $exception->getMessage(), \OCP\Util::ERROR);
					return false;
				}
				$contents = array();
				if ($response) {
					// Cache folder's contents
					foreach ($response as $file) {
							$this->metaData[$file->getPathDisplay()] = $file;
							$contents[] = $file;
					}
					unset($response);
				}
				// Return contents of folder only
				return $contents;
			} else {
				try {
					$requestPath = $path;
					if ($path !== '.' && $path !== '/' && $path !== '') {
					    $response = $this->dropbox->getMetaData($requestPath);
					    $this->metaData[$response->getPathDisplay()] = $response;
					}
					return $response;
				} catch (\Exception $exception) {
					if ($exception instanceof DropboxClientException) {
						// don't log, might be a file_exist check
						return false;
					}
					\OCP\Util::writeLog('files_external_moe', $exception->getMessage(), \OCP\Util::ERROR);
					return false;
				}
			}
		}
	}

	public function getId(){
		return $this->id;
	}

	public function mkdir($path) {
		$path = $this->root.$path;
		try {
			$this->dropbox->createFolder($path);
			return true;
		} catch (\Exception $exception) {
			\OCP\Util::writeLog('dropbox2_mkdir', $exception->getMessage(), \OCP\Util::ERROR);
			return false;
		}
	}

	public function rmdir($path) {
		return $this->unlink($path);
	}

	public function opendir($path) {
	    $path = $this->root.$path;
		$listFolderContents = $this->dropbox->listFolder($path);
		$items = $listFolderContents->getItems();
		if ($items !== false) {
			$files = array();
			foreach ($items as $file) {
				$files[] = basename($file->getPathDisplay());
			}
			return IteratorDirectory::wrap($files);
		}
		return false;
	}

	public function stat($path) {
	    $stat = array();
	    
	    if ($path === '.' || $path === '/' || $path === '') {
	        $stat['size'] = $this->dropbox->getSpaceUsage()['used'];
	        $stat['atime'] = 0;
	        $stat['mtime'] = 0;
	        return $stat;
	    }
	    else if ($this->filetype($path) == 'dir'){
	        $stat['size'] = 0;
	        $stat['atime'] = 0;
	        $stat['mtime'] = 0;
	        return $stat;
	    }
	    
		$metaData = $this->getDropBoxMetaData($path);
		
		if ($metaData) {
			$stat['size'] = $metaData->getDataProperty('size');
			$stat['atime'] = time();
			$stat['mtime'] = strtotime($metaData->getDataProperty('server_modified'));
			return $stat;
		}
		return false;
	}

	public function filetype($path) {
		if ($path === '' || $path === '/' || $path === '.') {
			return 'dir';
		} else {
			$metaData = $this->getDropBoxMetaData($path);
			if ($metaData) {
				if ($metaData->getDataProperty('.tag') == 'folder') {
					return 'dir';
				} else {
					return 'file';
				}
			}
		}
		return false;
	}

	public function file_exists($path) {
		if ($path === '' || $path === '/' || $path === '.') {
			return true;
		}
		if ($this->getDropBoxMetaData($path)) {
			return true;
		}
		return false;
	}

	public function unlink($path) {
		try {
		    $result = false;
		    $file = $this->getDropBoxMetaData($path);
		    if ($file){
    			$this->dropbox->delete($this->root.$path);
    			$this->deleteMetaData($path);
    			return true;
		    }
			return $result;
		} catch (\Exception $exception) {
			\OCP\Util::writeLog('dropbox2_unlink', $exception->getMessage(), \OCP\Util::ERROR);
			return false;
		}
	}

	public function rename($path1, $path2) {
		try {
			// overwrite if target file exists and is not a directory
			$destMetaData = $this->getDropBoxMetaData($path2);
			if (isset($destMetaData) && $destMetaData !== false) {
				$this->unlink($path2);
			}
			$this->dropbox->move($this->root.$path1, $this->root.$path2);
			$this->deleteMetaData($path1);
			return true;
		} catch (\Exception $exception) {
			\OCP\Util::writeLog('dropbox2_rename', $exception->getMessage(), \OCP\Util::ERROR);
			return false;
		}
	}

	public function copy($path1, $path2) {
		$path1 = $this->root.$path1;
		$path2 = $this->root.$path2;
		try {
			$this->dropbox->copy($path1, $path2);
			return true;
		} catch (\Exception $exception) {
			\OCP\Util::writeLog('dropbox2_copy', $exception->getMessage(), \OCP\Util::ERROR);
			return false;
		}
	}

	public function fopen($path, $mode) {
		$path = $this->root.$path;
		switch ($mode) {
			case 'r':
			case 'rb':
				$tmpFile = \OCP\Files::tmpFile();
				try {
					$file = $this->dropbox->download($path);
					$contents = $file->getContents();
					file_put_contents($tmpFile, $contents);
					return fopen($tmpFile, 'r');
				} catch (\Exception $exception) {
					\OCP\Util::writeLog('dropbox2_fopen', $exception->getMessage(), \OCP\Util::ERROR);
					return false;
				}
			case 'w':
			case 'wb':
			case 'a':
			case 'ab':
			case 'r+':
			case 'w+':
			case 'wb+':
			case 'a+':
			case 'x':
			case 'x+':
			case 'c':
			case 'c+':
				if (strrpos($path, '.') !== false) {
					$ext = substr($path, strrpos($path, '.'));
				} else {
					$ext = '';
				}
				$tmpFile = \OCP\Files::tmpFile($ext);
				\OC\Files\Stream\Close::registerCallback($tmpFile, array($this, 'writeBack'));
				if ($this->file_exists($path)) {
					$source = $this->fopen($path, 'r');
					file_put_contents($tmpFile, $source);
				}
				self::$tempFiles[$tmpFile] = $path;
				return fopen('close://'.$tmpFile, $mode);
		}
		return false;
	}

	public function writeBack($tmpFile) {
		if (isset(self::$tempFiles[$tmpFile])) {
		    $path = self::$tempFiles[$tmpFile]; 
		    $path = substr($path,1,strlen($path));
		    $this->unlink($path);
		    
			$handle = fopen($tmpFile, 'r');
			try {
			    $dropboxFile = new DropboxFile($tmpFile);
			    $file = $this->dropbox->upload($dropboxFile, self::$tempFiles[$tmpFile], ['autorename' => false]);
				unlink($tmpFile);
				$this->deleteMetaData(self::$tempFiles[$tmpFile]);
			} catch (\Exception $exception) {
				\OCP\Util::writeLog('dropbox2_writeBack', $exception->getMessage(), \OCP\Util::ERROR);
			}
		}
	}

	public function getMimeType($path) {
		if ($this->filetype($path) == 'dir') {
			return 'httpd/unix-directory';
		} else {
		        $mimetype = \OC::$server->getMimeTypeDetector()->detect($path);
		        return $mimetype;
		}
		return false;
	}

	public function free_space($path) {
	    //{\"used\":969262,\"allocation\":{\".tag\":\"individual\",\"allocated\":2147483648}}
		try {
			$info = $this->dropbox->getSpaceUsage();
			return $info['allocation']['allocated'] - $info['used'];
		} catch (\Exception $exception) {
			\OCP\Util::writeLog('dropbox2_free_space', $exception->getMessage(), \OCP\Util::ERROR);
			return false;
		}
	}

	public function touch($path, $mtime = null) {
		if ($this->file_exists($path)) {
			return false;
		} else {
		    //$tmpFile = '/tmp/'.$path;
                    $tmpFile = \OCP\Files::tmpFile();
		    touch($tmpFile);
			$dropboxFile = new DropboxFile($tmpFile);
			$file = $this->dropbox->upload($dropboxFile, $this->root.$path, ['autorename' => true]);
			unlink($tmpFile);
		}
		return true;
	}
        public function hasUpdated($path, $time) {
	    $size = \OC::$server->getConfig()->getUserValue(\OC_User::getUser(), "dropbox", "size",0);
	    $freeSize = $this->free_space($path);
	    if ($freeSize != $size){
	        \OC::$server->getConfig()->setUserValue(\OC_User::getUser(), "dropbox", "size",$freeSize);
	        return true;
	    }
	    return false;
	}
	public function test() {
	    if ($this->free_space('')) {
	        
	        return true;
	    }
	    return false;
	}
	
	/**
	 * check if curl is installed
	 */
	public static function checkDependencies() {
		return true;
	}

}

