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
 * @package filedownloadr
 * @subpackage build
 *
 * @param type $filename
 * @return type
 */

function getSnippetContent($filename) {
    $o = file_get_contents($filename);
    $o = str_replace('<?php', '', $o);
    $o = str_replace('?>', '', $o);
    $o = trim($o);
    return $o;
}

$snippets = array();

$snippets[0] = $modx->newObject('modSnippet');
$snippets[0]->fromArray(array(
    'id' => 0,
    'name' => 'FileDownload',
    'description' => 'Snippet to list downloadable files.',
    'snippet' => getSnippetContent($sources['source_core'].'/elements/snippets/filedownload.snippet.php'),
        ), '', true, true);
$properties = include $sources['properties'] . 'filedownloadr.properties.php';
$snippets[0]->setProperties($properties);
unset($properties);

$snippets[1] = $modx->newObject('modSnippet');
$snippets[1]->fromArray(array(
    'id' => 1,
    'name' => 'FileDownloadLink',
    'description' => 'Snippet to provide a download link for a file.',
    'snippet' => getSnippetContent($sources['source_core'].'/elements/snippets/filedownloadlink.snippet.php'),
        ), '', true, true);
$properties = include $sources['properties'] . 'filedownloadlink.properties.php';
$snippets[1]->setProperties($properties);
unset($properties);

$snippets[2] = $modx->newObject('modSnippet');
$snippets[2]->fromArray(array(
    'id' => 2,
    'name' => 'FileDownloadEmailPlugin',
    'description' => 'Snippet as a plugin for FileDownload R\'s.',
    'snippet' => getSnippetContent($sources['source_core'].'/plugins/filedownloadr.formit.email.plugin.php'),
        ), '', true, true);

return $snippets;