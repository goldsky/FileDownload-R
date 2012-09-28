<?php

/**
 * avoid FATAL ERROR
 */
if (!($plugin instanceof FileDownloadPlugin) ||
        !($modx instanceof modX) ||
        !($fileDownload instanceof FileDownload)
) {
    return FALSE;
}

$plugin;
$modx;
$fileDownload;
$properties = $plugin->getProperties();
$events = $plugin->getEvents();
$e = $plugin->getEvent();
//echo __LINE__ . " : __FILE__ = " . __FILE__ . "<br />";
//echo __LINE__ . " : \$e = " . $e . "<br />";
//echo __LINE__ . " : \$events = " . $events . "<br />";
//echo "<pre>";
//print_r($events);
//echo "</pre>";

switch ($e) {
    case 'OnLoad':
        return 'OnLoad';

        break;
    case 'BeforeFileDownload':

        return FALSE;

        break;
    default:
        break;
}
return FALSE;