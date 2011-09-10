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
 * Resolve creating db tables
 *
 * @package filedownload
 * @subpackage build
 */
/* set some default values */
$output = '';
/* get values based on mode */
switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:
        break;
    case xPDOTransport::ACTION_UPGRADE:
        break;
    case xPDOTransport::ACTION_UNINSTALL:
        $modx = & $object->xpdo;
        $modelPath = $modx->getOption('core_path') . 'components/filedownload/models/';
        $modx->addPackage('filedownload', realpath($modelPath) . DIRECTORY_SEPARATOR);
        $cat = $modx->getObject('FDL');
        if (!$cat) {
            $modx->log(xPDO::LOG_LEVEL_INFO,'realpath($modelPath) . DIRECTORY_SEPARATOR = ' . realpath($modelPath) . DIRECTORY_SEPARATOR);
            $modx->log(xPDO::LOG_LEVEL_INFO,'var_dump($cat) = ' . var_dump($cat));
            $modx->log(xPDO::LOG_LEVEL_INFO,'[FileDownload] could not load the filedownload package while uninstalling.');
        } else {
            $modx->log(xPDO::LOG_LEVEL_INFO,'[FileDownload] pass through the xPDOTransport::ACTION_UNINSTALL');
        }
        break;
}

if ($cat) {
    /* do output html */
    $output = '
<h2>FileDownload Uninstaller</h2>
<p>You are about to uninstall FileDownload snippet. Do you also want to remove the FileDownload\'s database?</p>
<br />
<input type="checkbox" name="fdl_keep_db" id="fdl_keep_db" value="1" selected="selected" />
<p>It is recommended if you keep the download countings.</p>
<br /><br />
';
}

return $output;