<?php
/**
 * Copyright (c) 2016 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Memcache\AppInfo;

$app = new Application();

$manager = $app->getManager();
\OCP\Util::connectHook('OC_Filesystem', 'preSetup', $manager, 'setupStorageWrapper');
