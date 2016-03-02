<?php
/**
 * Copyright (c) 2016 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Memcache\Wrapper;

use OCP\Files\Cache\IPropagator;

class MemcachePropagator implements IPropagator {
	/**
	 * @var \OCP\ICache
	 */
	private $cache;

	/**
	 * MemcachePropagator constructor.
	 *
	 * @param \OCP\ICache $cache
	 */
	public function __construct(\OCP\ICache $cache) {
		$this->cache = $cache;
	}

	public function propagateChange($internalPath, $time, $sizeDifference = 0) {
		$parents = $this->getParents($internalPath);
		foreach ($parents as $parent) {
			$this->cache->remove('etag/' . $parent); //trigger etag update next time we get fileinfo
		}
	}

	protected function getParents($path) {
		if ($path === '') {
			return [];
		}
		$parts = explode('/', dirname($path));
		$parent = '';
		$parents = [];
		foreach ($parts as $part) {
			$parents[] = $parent;
			$parent = trim($parent . '/' . $part, '/');
		}
		return $parents;
	}
}
