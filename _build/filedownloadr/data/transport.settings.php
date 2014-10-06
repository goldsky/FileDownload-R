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
$settings['filedownloadr.core_path'] = $modx->newObject('modSystemSetting');
$settings['filedownloadr.core_path']->fromArray(array(
    'key' => 'filedownload.core_path',
    'value' => '{core_path}components/filedownload/',
    'xtype' => 'textfield',
    'namespace' => 'filedownloadr',
    'area' => 'URL',
        ), '', true, true);

$settings['filedownloadr.assets_url'] = $modx->newObject('modSystemSetting');
$settings['filedownloadr.assets_url']->fromArray(array(
    'key' => 'filedownload.assets_url',
    'value' => '{assets_url}components/filedownload/',
    'xtype' => 'textfield',
    'namespace' => 'filedownloadr',
    'area' => 'URL',
        ), '', true, true);

$settings['filedownloadr.exclude_scan'] = $modx->newObject('modSystemSetting');
$settings['filedownloadr.exclude_scan']->fromArray(array(
    'key' => 'filedownload.exclude_scan',
    'value' => '.,..,Thumbs.db,.htaccess,.htpasswd,.ftpquota,.DS_Store',
    'xtype' => 'textfield',
    'namespace' => 'filedownloadr',
    'area' => 'file',
        ), '', true, true);

return $settings;
