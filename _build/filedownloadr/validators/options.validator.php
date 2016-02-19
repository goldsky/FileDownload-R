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
 * Validates deleting db tables by deleting table options.
 *
 * @package filedownloadr
 * @subpackage build
 */
if ($modx = & $object->xpdo) {
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            break;
        case xPDOTransport::ACTION_UNINSTALL:
            if (empty($options['fdl_keep_db'])) {
                $modelPath = $modx->getOption('core_path') . 'components/filedownloadr/model/';
                $modelPath = realpath($modelPath) . DIRECTORY_SEPARATOR;
                $tablePrefix = $modx->getOption('filedownloadr.table_prefix', null, $modx->config[modX::OPT_TABLE_PREFIX] . 'fd_');
                if ($modx->addPackage('filedownloadr', $modelPath, $tablePrefix)) {
                    $manager = $modx->getManager();
                    if (!$manager->removeObjectContainer('fdCount')) {
                        $modx->log(modX::LOG_LEVEL_ERROR, '[FileDownload] table was unable to be deleted');
                        return false;
                    }
                    $modx->log(modX::LOG_LEVEL_INFO, '[FileDownload] table was deleted successfully');
                }
            }

            break;
    }
}
return true;