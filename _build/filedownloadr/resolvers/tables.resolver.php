<?php

/**
 * FileDownload
 *
 * Copyright 2011-2016 by goldsky <goldsky@virtudraft.com>
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
 * Resolve creating db tables
 *
 * @package filedownloadr
 * @subpackage build
 */
if ($modx = & $object->xpdo) {
    $c = $modx->newQuery('transport.modTransportPackage');
    $c->where(array(
        'workspace' => 1,
        "(SELECT
            `signature`
          FROM {$modx->getTableName('modTransportPackage')} AS `latestPackage`
          WHERE `latestPackage`.`package_name` = `modTransportPackage`.`package_name`
          ORDER BY
             `latestPackage`.`version_major` DESC,
             `latestPackage`.`version_minor` DESC,
             `latestPackage`.`version_patch` DESC,
             IF(`release` = '' OR `release` = 'ga' OR `release` = 'pl','z',`release`) DESC,
             `latestPackage`.`release_index` DESC
          LIMIT 1,1) = `modTransportPackage`.`signature`",
    ));
    $c->where(array(
        'modTransportPackage.signature:LIKE' => 'filedownloadr%',
        'OR:modTransportPackage.package_name:LIKE' => 'filedownloadr%',
        'AND:installed:IS NOT' => null
    ));
    $oldPackage = $modx->getObject('transport.modTransportPackage', $c);

    function convertCount(modX $modx, $offset) {
        $c = $modx->newQuery('fdCount');
        $c->limit(1000, $offset);
        $oldCounts = $modx->getCollection('fdCount', $c);
        if ($oldCounts) {
            foreach ($oldCounts as $oldCount) {
                $oldCountArray = $oldCount->toArray();
                $path = $modx->getObject('fdPaths', array(
                    'ctx' => $oldCountArray['ctx'],
                    'filename' => $oldCountArray['filename'],
                    'hash' => $oldCountArray['hash'],
                ));
                if (!empty($path)) {
                    $oldCount->remove(); // remove?
                    continue;
                }
                $path = $modx->newObject('fdPaths');
                $path->fromArray(array(
                    'ctx' => $oldCountArray['ctx'],
                    'media_source_id' => 0,
                    'filename' => $oldCountArray['filename'],
                    'hash' => $oldCountArray['hash'],
                ));
                $downloads = array();
                for ($i = 0; $i < $oldCountArray['count'] ; $i++) {
                    $download = $modx->newObject('fdDownloads');
                    $download->fromArray(array(
                        'timestamp' => time(),
                    ));
                    $downloads[] = $download;
                }
                $path->addMany($downloads);
                if ($path->save() === false) {
                    continue;
                }
                $oldCount->remove(); // remove?
            }
        }
    }

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
            $modelPath = $modx->getOption('core_path') . 'components/filedownloadr/model/';
            $modelPath = realpath($modelPath) . DIRECTORY_SEPARATOR;
            $tablePrefix = $modx->getOption('filedownloadr.table_prefix', null, $modx->config[modX::OPT_TABLE_PREFIX] . 'fd_');
            if (!$modx->addPackage('filedownloadr', $modelPath, $tablePrefix)) {
                $modx->log(modX::LOG_LEVEL_ERROR, "[FileDownloadR] was unable to load this package");
                return false;
            }
            $manager = $modx->getManager();
            if (!$manager->createObjectContainer('fdDownloads')) {
                $modx->log(modX::LOG_LEVEL_ERROR, "[FileDownloadR] was unable to create `{$tablePrefix}downloads` table");
                return false;
            }
            if (!$manager->createObjectContainer('fdPaths')) {
                $modx->log(modX::LOG_LEVEL_ERROR, "[FileDownloadR] was unable to create `{$tablePrefix}paths` table");
                return false;
            }
            break;
        case xPDOTransport::ACTION_UPGRADE:
            $modelPath = $modx->getOption('core_path') . 'components/filedownloadr/model/';
            $modelPath = realpath($modelPath) . DIRECTORY_SEPARATOR;
            $tablePrefix = $modx->getOption('filedownloadr.table_prefix', null, $modx->config[modX::OPT_TABLE_PREFIX] . 'fd_');
            if (!$modx->addPackage('filedownloadr', $modelPath, $tablePrefix)) {
                $modx->log(modX::LOG_LEVEL_ERROR, "[FileDownloadR] was unable to load this package");
                return false;
            }
            $manager = $modx->getManager();
            $manager->createObjectContainer('fdDownloads');
            $manager->createObjectContainer('fdPaths');

            if ($oldPackage) {
                if ($oldPackage->compareVersion('2.0.0-beta1', '>') &&
                        $oldPackage->compareVersion('2.0.0-beta1', '!=')
                ) {
                    $count = (int) $modx->getCount('fdCount');
                    if ($count > 0) {
                        $modx->log(modX::LOG_LEVEL_INFO, "[FileDownloadR] is starting to convert the database...");
                        $split = ceil($count / 1000); // limit
                        for ($index = 0; $index < $split; $index++) {
                            $offset = $index * 10;
                            convertCount($modx, $offset);
                        }
                        $modx->log(modX::LOG_LEVEL_INFO, "[FileDownloadR] conversion is finished!");
                    }
                }
            }

            break;
        case xPDOTransport::ACTION_UNINSTALL:
            break;
    }
}

return true;
