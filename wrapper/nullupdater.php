<?php
/**
 * Copyright (c) 2016 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Memcache\Wrapper;

use OCP\Files\Cache\IPropagator;
use OCP\Files\Cache\IUpdater;
use OCP\Files\Storage\IStorage;

/**
 * Update the cache and propagate changes
 *
 */
class NullUpdater implements IUpdater {
	/**
	 * @var IStorage
	 */
	private $storage;

	/**
	 * NullUpdater constructor.
	 *
	 * @param IStorage $storage
	 */
	public function __construct(IStorage $storage) {
		$this->storage = $storage;
	}


	public function propagate($path, $time = null) {
		$this->storage->getPropagator()->propagateChange($path, $time);
	}

	public function update($path, $time = null) {
		$this->propagate($path, $time);
	}

	public function remove($path) {
		$this->propagate($path);
	}

	public function renameFromStorage(IStorage $sourceStorage, $source, $target) {
		$sourceStorage->getUpdater()->remove($source);
		if ($sourceStorage === $this->storage) {
			$sourceStorage->getCache()->move($source, $target);
		}
		$this->propagate($source);
		$this->propagate($target);
	}

	/**
	 * Get the propagator for etags and mtime for the view the updater works on
	 *
	 * @return IPropagator
	 * @since 9.0.0
	 */
	public function getPropagator() {
		return $this->storage->getPropagator();
	}


}
