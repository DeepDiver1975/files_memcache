<?php
/**
 * Copyright (c) 2016 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Memcache\Wrapper;

use OC\Files\Storage\Wrapper\Wrapper;

/**
 * Specialized version of Local storage with memcache as filecache
 */
class MemcacheWrapper extends Wrapper {
	/**
	 * @var \OC\Files\Storage\Local $storage
	 */
	protected $storage;

	/**
	 * @var \OCP\ICache
	 */
	protected $memcache;

	private $propagator;
	private $updater;
	private $scanner;
	private $cache;

	/**
	 * @param array $parameters
	 */
	public function __construct($parameters) {
		parent::__construct($parameters);
		$this->memcache = $parameters['memcache'];
	}

	/**
	 * @param string $path
	 * @param \OC\Files\Storage\Local|null $storage
	 * @return \OC\Files\Cache\HomeCache
	 */
	public function getCache($path = '', $storage = null) {
		if (!isset($this->cache)) {
			$this->cache = new MemcacheCache($this->storage, $this->memcache);
		}
		return $this->cache;
	}

	public function getScanner($path = '', $storage = null) {
		if (!isset($this->scanner)) {
			$this->scanner = new NullScanner();
		}
		return $this->scanner;
	}

	/**
	 * get a propagator instance for the cache
	 *
	 * @param \OC\Files\Storage\Storage (optional) the storage to pass to the watcher
	 * @return \OC\Files\Cache\Propagator
	 */
	public function getPropagator($storage = null) {
		if (!isset($this->propagator)) {
			$this->propagator = new MemcachePropagator($this->memcache);
		}
		return $this->propagator;
	}

	public function getUpdater($storage = null) {
		if (!isset($this->updater)) {
			$this->updater = new NullUpdater($this);
		}
		return $this->updater;
	}

	public function hasUpdated($path, $time) {
		return false;
	}
}
