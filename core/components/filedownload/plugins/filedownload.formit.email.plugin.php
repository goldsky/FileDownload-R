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

//$props = $plugin->getProperties();
//$allEvents = $plugin->getAllEvents();
//$appliedEvents = $plugin->getAppliedEvents();

$e = $plugin->getEvent();
switch ($e) {
    case 'AfterFileDownload':
        // don't bother about the IP Address. FormSave provides it.
        $props = $plugin->getProperties();
        $_POST = array(
            'ctx' => $props['ctx'],
            'filePath' => $props['filePath'],
        );
        $_REQUEST = $_POST;
        $runFormit = $modx->runSnippet('FormIt', array(
            'hooks' => 'email',
            'emailTpl' => 'FileDownloadEmailChunk',
            'emailSubject' => 'New Downloader',
            'emailTo' => 'goldsky@virtudraft.com',
            'emailCC' => 'goldsky.milis@gmail.com',
            'emailBCC' => 'goldsky@fastmail.fm',
            'emailBCCName' => 'goldsky',
                ));
        if ($runFormit === FALSE) {
            $errMsg = '[FileDownloadPlugin FormSave] unabled to save the downloader into FormSave';
            $modx->setPlaceholder('fd.error_message', $errMsg);
            $modx->log(modX::LOG_LEVEL_ERROR, __LINE__ . ': ' . $errMsg);
            return FALSE;
        }
        break;
    default:
        break;
}

return TRUE;