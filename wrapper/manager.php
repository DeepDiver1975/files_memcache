<?php
/**
 * Copyright (c) 2016 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Memcache\Wrapper;

use OC\Files\Filesystem;
use OC\Files\Storage\Local;
use OC\Files\Storage\Storage;
use OCP\ICache;

class Manager {
	/**
	 * @var ICache
	 */
	private $memcache;

	/**
	 * Manager constructor.
	 *
	 * @param ICache $memcache
	 */
	public function __construct(ICache $memcache) {
		$this->memcache = $memcache;
	}

	public function setupStorageWrapper() {
		Filesystem::addStorageWrapper('memcache', function ($mountPoint, Storage $storage) {
			if ($storage instanceof Local) {
				return new MemcacheWrapper([
					'storage' => $storage,
					'memcache' => $this->memcache
				]);
			} else {
				return $storage;
			}
		}, 99999); // always apply first
	}
}
