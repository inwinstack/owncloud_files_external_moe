<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OCA\Files_External_MOE\Lib;

use OC\Files\Mount\MountPoint;
use OC\Files\Mount\MoveableMount;
use OCA\Files_External_MOE\Service\UserStoragesService;

/**
 * Person mount points can be moved by the user
 */
class PersonalMount extends MountPoint implements MoveableMount {
	/** @var UserStoragesService */
	protected $storagesService;

	/** @var int */
	protected $storageId;

	/**
	 * @param UserStoragesService $storagesService
	 * @param int $storageId
	 * @param string|\OC\Files\Storage\Storage $storage
	 * @param string $mountpoint
	 * @param array $arguments (optional) configuration for the storage backend
	 * @param \OCP\Files\Storage\IStorageFactory $loader
	 * @param array $mountOptions mount specific options
	 */
	public function __construct(
		UserStoragesService $storagesService,
		$storageId,
		$storage,
		$mountpoint,
		$arguments = null,
		$loader = null,
		$mountOptions = null
	) {
		parent::__construct($storage, $mountpoint, $arguments, $loader, $mountOptions);
		$this->storagesService = $storagesService;
		$this->storageId = $storageId;
	}

	/**
	 * Move the mount point to $target
	 *
	 * @param string $target the target mount point
	 * @return bool
	 */
	public function moveMount($target) {
		$storage = $this->storagesService->getStorage($this->storageId);
		// remove "/$user/files" prefix
		$targetParts = explode('/', trim($target, '/'), 3);
		$storage->setMountPoint($targetParts[2]);
		$this->storagesService->updateStorage($storage);
		$this->setMountPoint($target);
		return true;
	}

	/**
	 * Remove the mount points
	 *
	 * @return bool
	 */
	public function removeMount() {
		$this->storagesService->removeStorage($this->storageId);
		return true;
	}
}
