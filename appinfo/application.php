<?php
/**
 * Copyright (c) 2016 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Memcache\AppInfo;

use OCA\Files_Memcache\Wrapper\Manager;
use \OCP\AppFramework\App;

class Application extends App {
	public function __construct(array $urlParams = array()) {
		parent::__construct('files_memcache', $urlParams);
	}

	public function getManager() {
		$cacheFactory = $this->getContainer()->getServer()->getMemCacheFactory();
		return new Manager($cacheFactory->create('files_memcache'));
	}
}
