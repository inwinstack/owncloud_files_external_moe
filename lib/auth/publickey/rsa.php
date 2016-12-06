<?php
/**
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

namespace OCA\Files_External_MOE\Lib\Auth\PublicKey;

use \OCP\IL10N;
use \OCA\Files_External_MOE\Lib\DefinitionParameter;
use \OCA\Files_External_MOE\Lib\Auth\AuthMechanism;
use \OCA\Files_External_MOE\Lib\StorageConfig;
use \OCP\IConfig;
use \phpseclib\Crypt\RSA as RSACrypt;

/**
 * RSA public key authentication
 */
class RSA extends AuthMechanism {

	const CREATE_KEY_BITS = 1024;

	/** @var IConfig */
	private $config;

	public function __construct(IL10N $l, IConfig $config) {
		$this->config = $config;

		$this
			->setIdentifier('publickey::rsa')
			->setScheme(self::SCHEME_PUBLICKEY)
			->setText($l->t('RSA public key'))
			->addParameters([
				(new DefinitionParameter('user', $l->t('Username'))),
				(new DefinitionParameter('public_key', $l->t('Public key'))),
				(new DefinitionParameter('private_key', 'private_key'))
					->setType(DefinitionParameter::VALUE_HIDDEN),
			])
			->setCustomJs('public_key')
		;
	}

	public function manipulateStorageConfig(StorageConfig &$storage) {
		$auth = new RSACrypt();
		$auth->setPassword($this->config->getSystemValue('secret', ''));
		if (!$auth->loadKey($storage->getBackendOption('private_key'))) {
			throw new \RuntimeException('unable to load private key');
		}
		$storage->setBackendOption('public_key_auth', $auth);
	}

	/**
	 * Generate a keypair
	 *
	 * @return array ['privatekey' => $privateKey, 'publickey' => $publicKey]
	 */
	public function createKey() {
		$rsa = new RSACrypt();
		$rsa->setPublicKeyFormat(RSACrypt::PUBLIC_FORMAT_OPENSSH);
		$rsa->setPassword($this->config->getSystemValue('secret', ''));

		return $rsa->createKey(self::CREATE_KEY_BITS);
	}

}
