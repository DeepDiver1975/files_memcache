<?php
/**
 * Copyright (c) 2016 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Memcache\Wrapper;

use OC\Files\Cache\CacheEntry;
use OC\Files\Storage\Local;
use OCP\Files\Cache\ICache;

class MemcacheCache implements ICache {
	const FOLDER_MIME = 'httpd/unix-directory';

	/**
	 * @var \OC\Files\Storage\Local
	 */
	private $storage;

	/**
	 * @var \OCP\ICache
	 */
	private $cache;

	private $idMap = [];

	/**
	 * @param string $path
	 * @return CacheEntry|bool
	 */
	public function get($path) {
		if (!$this->storage->file_exists($path)) {
			return false;
		}

		$data = $this->storage->getMetaData($path);
		$data['path'] = $path;
		$data['name'] = basename($path);
		$stat = $this->storage->stat($path);
		$id = $stat['ino']; //note: might conflict with non local storages
		$data['fileid'] = $id;
		$mimeParts = explode('/', $data['mimetype'], 2);
		$data['mimepart'] = $mimeParts[0];
		$data['storage_mtime'] = $data['mtime'];
		$data['parent'] = $this->getParentId($path);
		if ($data['mimetype'] === self::FOLDER_MIME) {
			// folder etag persistence
			$etag = $this->cache->get('etag/' . $path);
			if ($etag) {
				$data['etag'] = $etag;
			} else {
				$this->cache->set('etag/' . $path, $data['etag']);
			}
			$data['size'] = 1;//TODO save in memcache
		}
		$data['encrypted'] = false;

		if (!isset($this->idMap[$id])) {
			$this->idMap[$id] = $path;
			$this->cache->set('idMap/' . $id, $path);
		}

		return new CacheEntry($data);
	}

	/**
	 * @param \OC\Files\Storage\Local $storage
	 * @param \OCP\ICache $cache
	 */
	public function __construct(Local $storage, \OCP\ICache $cache) {
		$this->storage = $storage;
		$this->cache = $cache;
	}

	/**
	 * get the metadata of all files stored in $folder
	 *
	 * @param string $folder
	 * @return array
	 */
	public function getFolderContents($folder) {
		$dh = $this->storage->opendir($folder);
		$files = [];
		while (($file = readdir($dh)) !== false) {
			$files[] = $folder . '/' . $file;
		}
		closedir($dh);
		$files = array_filter($files, function ($file) {
			return basename($file) !== '.' && basename($file) !== '..';
		});
		$files = array_values($files);
		return array_map(function ($file) {
			return $this->get($file);
		}, $files);
	}

	/**
	 * get the metadata of all files stored in $folder
	 *
	 * @param int $fileId the file id of the folder
	 * @return array
	 */
	public function getFolderContentsById($fileId) {
		$path = $this->getPathById($fileId);
		return $this->getFolderContents($path);
	}

	public function put($file, array $data) {
		// shouldn't be called due to the custom updater
		throw new \Exception('Not supported');
	}

	public function update($id, array $data) {
		// shouldn't be called due to the custom updater
		throw new \Exception('Not supported');
	}

	public function getId($file) {
		$stat = $this->storage->stat($file);
		return $stat['ino']; //note: might conflict with non local storages
	}

	public function remove($file) {
		// shouldn't be called due to the custom updater
		throw new \Exception('Not supported');
	}

	public function move($source, $target) {
		// update the id mapping
		$info = $this->get($source);
		$this->idMap[$info->getId()] = $target;
		$this->cache->set('idMap/' . $info->getId(), $target);
	}

	public function moveFromCache(ICache $sourceCache, $sourcePath, $targetPath) {
		// shouldn't be called due to the custom updater
		throw new \Exception('Not supported');
	}

	public function getStatus($file) {
		return $this->storage->file_exists($file) ? ICache::COMPLETE : ICache::NOT_FOUND;
	}

	public function search($pattern) {
		// not possible to implement with decent performance for this cache
		return [];
	}

	public function searchByMime($mimetype) {
		// not possible to implement with decent performance for this cache
		return [];
	}

	public function searchByTag($tag, $userId) {
		// not possible to implement with decent performance for this cache
		return [];
	}

	public function getAll() {
		// mainly used to populate the id mapping
		$inFolders = [''];
		$result = [];

		while (count($inFolders) > 0) {
			$folder = array_pop($inFolders);

			$content = $this->getFolderContents($folder);
			$result = array_merge($result, $content);

			$subFolders = array_filter($content, function (array $data) {
				return $data['mimetype'] === self::FOLDER_MIME;
			});
			$subFolderPaths = array_map(function (array $data) {
				return $data['path'];
			}, $subFolders);

			$inFolders = array_merge($inFolders, $subFolderPaths);
		}

		return $result;
	}

	public function getIncomplete() {
		// nothing needs to be scanned
		return [];
	}

	public function getPathById($id) {
		// first see if we already know the path
		if (isset($this->idMap[$id])) {
			return $this->idMap[$id];
		}

		// than check the memcache for the path
		$path = $this->cache->get('idMap/' . $id);
		if ($path) {
			return $path;
		} else {
			// finally we populate the map by going trough all files
			$this->getAll();

			// if the id is now not in the map the id doesn't exist
			return isset($this->idMap[$id]) ? $this->idMap[$id] : null;
		}
	}

	/**
	 * Get the numeric storage id for this cache's storage
	 *
	 * @return int
	 * @since 9.0.0
	 */
	public function getNumericStorageId() {
		$stat = $this->storage->stat('');
		return $stat['dev']; //note: might conflict with non local storages
	}

	public function insert($file, array $data) {
		// shouldn't be called due to the custom updater
		throw new \Exception('Not supported');
	}

	/**
	 * get the id of the parent folder of a file
	 *
	 * @param string $file
	 * @return int
	 * @since 9.0.0
	 */
	public function getParentId($file) {
		if ($file === '') {
			return -1;
		}
		return $this->getId(dirname($file));
	}

	/**
	 * check if a file is available in the cache
	 *
	 * @param string $file
	 * @return bool
	 * @since 9.0.0
	 */
	public function inCache($file) {
		// everything is in cache
		return $this->storage->file_exists($file);
	}

	/**
	 * normalize the given path for usage in the cache
	 *
	 * @param string $path
	 * @return string
	 * @since 9.0.0
	 */
	public function normalize($path) {
		return trim(\OC_Util::normalizeUnicode($path), '/');
	}


}
