<?php
/**
 * @var modX $modx
 * @var $fileDownload FileDownloadR
 * @var $plugin FileDownloadPlugin
 */
// avoid FATAL ERROR
if (!($modx instanceof modX) ||
    !($fileDownload instanceof FileDownloadR) ||
    !($plugin instanceof FileDownloadPlugin)
) {
    return false;
}

//$props = $plugin->getProperties();
//$allEvents = $plugin->getAllEvents();
//$appliedEvents = $plugin->getAppliedEvents();

$e = $plugin->getEvent();
switch ($e) {
    case 'OnLoad':
        // check the dependencies
        $formIt = $modx->getObject('modSnippet', array('name' => 'FormIt'));
        $formSave = $modx->getObject('modSnippet', array('name' => 'FormSave'));
        if (!$formIt || !$formSave) {
            $errMsg = 'Unable to load FormIt or FormSave';
            $modx->setPlaceholder($fileDownload->getConfig('prefix') . 'error_message', $errMsg);
            $modx->log(modX::LOG_LEVEL_ERROR, __LINE__ . ': ' . $errMsg, '', 'FileDownloadPlugin FormSave');
            return false;
        }
        break;
    case 'AfterFileDownload':
        // don't bother about the IP Address. FormSave provides it.
        $props = $plugin->getProperties();
        $_POST = array(
            'ctx' => $props['ctx'],
            'filePath' => $props['filePath'],
        );
        $_REQUEST = $_POST;
        $runFormit = $modx->runSnippet('FormIt', array(
            'hooks' => 'FormSave',
            'fsFormTopic' => 'downloader',
            'fsFormFields' => 'ctx,filePath',
        ));
        if ($runFormit === false) {
            $errMsg = 'Unabled to save the downloader into FormSave';
            $modx->setPlaceholder($fileDownload->getConfig('prefix') . 'error_message', $errMsg);
            $modx->log(modX::LOG_LEVEL_ERROR, __LINE__ . ': ' . $errMsg, '', 'FileDownloadPlugin FormSave');
            return false;
        }
        break;
    default:
        break;
}

return true;