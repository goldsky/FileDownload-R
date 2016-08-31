<?php
/**
 * Resolve creating db tables
 *
 * THIS RESOLVER IS AUTOMATICALLY GENERATED, NO CHANGES WILL APPLY
 *
 * @package filedownloadr
 * @subpackage build
 */

if ($object->xpdo) {
    $modx =& $object->xpdo;
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $modelPath = $modx->getOption('filedownloadr.core_path', null, $modx->getOption('core_path') . 'components/filedownloadr/') . 'model/';
            
            $modx->addPackage('filedownloadr', $modelPath, null);


            $manager = $modx->getManager();

            $manager->createObjectContainer('fdCount');
            $manager->createObjectContainer('fdDownloads');
            $manager->createObjectContainer('fdPaths');

            break;
    }
}

return true;