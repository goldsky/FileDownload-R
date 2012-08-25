<?php
/**
 * This file is meant to control the processor files for FileDownload's AJAX
 * requests
 */
header('Expires: Thu, 1 Jan 1970 00:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

define('MODX_API_MODE', true);

include (dirname(dirname(dirname(dirname(__FILE__)))) . '/model/modx/modx.class.php');
$modx = new modX;

if (!$modx || !($modx instanceof modX)) {
    return '';
}

die();