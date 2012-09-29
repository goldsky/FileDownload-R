<?php

/**
 * avoid FATAL ERROR
 */
if (!($modx instanceof modX) ||
        !($fileDownload instanceof FileDownload) ||
        !($plugin instanceof FileDownloadPlugin)
) {
    return FALSE;
}

$e = $plugin->getEvent();
switch ($e) {
    case 'OnLoad':
        // check the dependencies
        $formIt = $modx->getObject('modSnippet', array('name' => 'FormIt'));
        $formSave = $modx->getObject('modSnippet', array('name' => 'FormSave'));
        if (!$formIt || !$formSave) {
            $errMsg = 'Unable to load FormIt or FormSave';
            $modx->setPlaceholder('fd.error_message', $errMsg);
            $modx->log(modX::LOG_LEVEL_ERROR, __LINE__ . ': ' . $errMsg);
            return FALSE;
        }
        return TRUE;

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
        if ($runFormit === FALSE) {
            $errMsg = 'Unable to save the downloader into FormSave';
            $modx->setPlaceholder('fd.error_message', $errMsg);
            $modx->log(modX::LOG_LEVEL_ERROR, __LINE__ . ': ' . $errMsg);
            return FALSE;
        }
        return TRUE;

        break;
    default:
        break;
}

return FALSE;