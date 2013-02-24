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
        $emailProps = $fileDownload->getConfig('emailProps');
        $emailProps = json_decode($emailProps, 1);
        $formitProps = array_merge(array('hooks' => 'email'), $emailProps);
        $runFormit = $modx->runSnippet('FormIt', $formitProps);
        if ($runFormit === FALSE) {
            $errMsg = '[FileDownloadPlugin Email] unabled to send email.';
            $modx->setPlaceholder($fileDownload->getConfig('prefix') . 'error_message', $errMsg);
            $modx->log(modX::LOG_LEVEL_ERROR, __LINE__ . ': ' . $errMsg);
            return FALSE;
        }
        break;
    default:
        break;
}

return TRUE;