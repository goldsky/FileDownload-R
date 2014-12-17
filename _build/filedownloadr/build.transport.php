<?php

/**
 * FileDownload
 *
 * Copyright 2011 by goldsky <goldsky@fastmail.fm>
 *
 * This file is part of FileDownload, a file downloader for MODX Revolution
 *
 * FileDownload is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * FileDownload is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * FileDownload; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * FileDownload build script
 *
 * @package filedownload
 * @subpackage build
 */
$mtime = microtime();
$mtime = explode(' ', $mtime);
$mtime = $mtime[1] + $mtime[0];
$tstart = $mtime;
set_time_limit(0);

define('PKG_NAME', 'FileDownloadR');
define('PKG_NAME_LOWER', 'filedownloadr'); // work around the extra's namespace

$fdl = $modx->getService('fdl'
        , 'FileDownload'
        , $modx->getOption('core_path') . 'components/filedownloadr/models/filedownload/'
);

if (!($fdl instanceof FileDownload)) {
    return 'instanceof error.';
}

define('PKG_VERSION', FileDownload::VERSION);
define('PKG_RELEASE', FileDownload::RELEASE);

/* override with your own defines here (see build.config.sample.php) */
require_once dirname(__FILE__) . '/build.config.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

$root = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR;
$sources = array(
    'root' => $root,
    'build' => BUILD_PATH,
    'data' => BUILD_PATH . 'data' . DIRECTORY_SEPARATOR,
    'properties' => realpath(BUILD_PATH . 'data/properties/') . DIRECTORY_SEPARATOR,
    'resolvers' => realpath(BUILD_PATH . 'resolvers/') . DIRECTORY_SEPARATOR,
    'validators' => realpath(BUILD_PATH . 'validators/') . DIRECTORY_SEPARATOR,
    'lexicon' => realpath(MODX_CORE_PATH . 'components/filedownloadr/lexicon/') . DIRECTORY_SEPARATOR,
    'docs' => realpath(MODX_CORE_PATH . 'components/filedownloadr/docs/') . DIRECTORY_SEPARATOR,
    'chunks' => realpath(MODX_CORE_PATH . 'components/filedownloadr/themes/default/chunks/') . DIRECTORY_SEPARATOR,
    'source_assets' => realpath(MODX_ASSETS_PATH . 'components/filedownloadr'),
    'source_core' => realpath(MODX_CORE_PATH . 'components/filedownloadr'),
);
unset($root);

$modx = new modX();
$modx->initialize('mgr');
echo '<pre>'; /* used for nice formatting of log messages */
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
flush();
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');

$modx->loadClass('transport.modPackageBuilder', '', false, true);
$builder = new modPackageBuilder($modx);
$builder->createPackage(PKG_NAME_LOWER, PKG_VERSION, PKG_RELEASE);
$builder->registerNamespace(PKG_NAME_LOWER, false, true, '{core_path}components/filedownloadr/');
$modx->getService('lexicon', 'modLexicon');
$modx->lexicon->load('filedownloadr:properties');

/**
 * SYSTEM SETTINGS
 */
$settings = include $sources['data'] . 'transport.settings.php';
if (!is_array($settings)) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'Could not package in settings.');
} else {
    $modx->log(modX::LOG_LEVEL_INFO, 'Packaging in System Settings...');
    $settingAttributes = array(
        xPDOTransport::UNIQUE_KEY => 'key',
        xPDOTransport::PRESERVE_KEYS => true,
        xPDOTransport::UPDATE_OBJECT => false,
    );
    foreach ($settings as $setting) {
        $settingVehicle = $builder->createVehicle($setting, $settingAttributes);
        $builder->putVehicle($settingVehicle);
    }
    $modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($settings) . ' System Settings done.');
    unset($settingVehicle, $settings, $setting, $settingAttributes);
}

/* create category */
$category = $modx->newObject('modCategory');
$category->set('id', 1);
$category->set('category', PKG_NAME);

/* add snippets */
$modx->log(modX::LOG_LEVEL_INFO, 'Adding in snippets.');
$snippets = include $sources['data'] . 'transport.snippets.php';
if (is_array($snippets)) {
	$category->addMany($snippets);
    $modx->log(modX::LOG_LEVEL_INFO, 'Adding in ' . count($snippets) . ' snippets done.');
} else {
	$modx->log(modX::LOG_LEVEL_FATAL, 'Adding snippets failed.');
}

/* add chunks */
$modx->log(modX::LOG_LEVEL_INFO, 'Adding in chunks...');
flush();
$chunks = include $sources['data'] . 'transport.chunks.php';
if (is_array($chunks)) {
	$category->addMany($chunks);
    $modx->log(modX::LOG_LEVEL_INFO, 'Adding in ' . count($chunks) . ' chunks done.');
} else {
	$modx->log(modX::LOG_LEVEL_FATAL, 'Adding chunks failed.');
}

/* create category vehicle */
$attr = array(
    xPDOTransport::UNIQUE_KEY => 'category',
    xPDOTransport::PRESERVE_KEYS => false,
    xPDOTransport::UPDATE_OBJECT => true,
    xPDOTransport::RELATED_OBJECTS => true,
    xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array(
        'Snippets' => array(
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',
        ),
        'Chunks' => array(
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',
        )
    )
);
$vehicle = $builder->createVehicle($category, $attr);

$modx->log(modX::LOG_LEVEL_INFO, 'Adding file resolvers to category...');
$vehicle->resolve('file', array(
    'source' => $sources['source_assets'],
    'target' => "return MODX_ASSETS_PATH . 'components/';",
));
$vehicle->resolve('file', array(
    'source' => $sources['source_core'],
    'target' => "return MODX_CORE_PATH . 'components/';",
));
$modx->log(modX::LOG_LEVEL_INFO, 'Adding in PHP resolvers...');
$vehicle->resolve('php', array(
    'source' => $sources['resolvers'] . 'tables.resolver.php',
));
$modx->log(modX::LOG_LEVEL_INFO, 'Adding in PHP validators...');
$vehicle->validate('php', array(
    'source' => $sources['validators'] . 'options.validator.php',
));
$builder->putVehicle($vehicle);
unset($vehicle);

/* now pack in the license file, readme and setup options */
$modx->log(modX::LOG_LEVEL_INFO, 'Adding package attributes and setup options...');
flush();
$builder->setPackageAttributes(array(
    'license' => file_get_contents($sources['docs'] . 'license.txt'),
    'readme' => file_get_contents($sources['docs'] . 'readme.txt'),
    'changelog' => file_get_contents($sources['docs'] . 'changelog.txt'),
    'setup-options' => array(
        'source' => $sources['build'] . 'setup.options.php'
    )
));


/* zip up package */
$modx->log(modX::LOG_LEVEL_INFO, 'Packing up transport package zip...');
flush();
$builder->pack();

$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tend = $mtime;
$totalTime = ($tend - $tstart);
$totalTime = sprintf("%2.4f s", $totalTime);

$modx->log(modX::LOG_LEVEL_INFO, "\n<br />Package Built.<br />\nExecution time: {$totalTime}\n");

exit();