<?php
/**
 * Copyright (c) 2016 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Memcache\Wrapper;

use OCP\Files\Cache\IScanner;

class NullScanner implements IScanner {
	public function backgroundScan() {
		return;
	}

	public function scan($path, $recursive = IScanner::SCAN_RECURSIVE, $reuse = -1, $lock = true) {
		throw new \Exception('Not supported');
	}

	public function scanFile($file, $reuseExisting = 0, $parentId = -1, $cacheData = null, $lock = true) {
		throw new \Exception('Not supported');
	}

	/**
	 * check if the file should be ignored when scanning
	 * NOTE: files with a '.part' extension are ignored as well!
	 *       prevents unfinished put requests to be scanned
	 *
	 * @param string $file
	 * @return boolean
	 * @since 9.0.0
	 */
	public static function isPartialFile($file) {
		if (pathinfo($file, PATHINFO_EXTENSION) === 'part') {
			return true;
		}
		if (strpos($file, '.part/') !== false) {
			return true;
		}

		return false;
	}
}
