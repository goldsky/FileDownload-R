<?php
if ($object->xpdo) {

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_UPGRADE:
            /** @var modX $modx */
            $modx =& $object->xpdo;

            // http://forums.modx.com/thread/88734/package-version-check#dis-post-489104
            $c = $modx->newQuery('transport.modTransportPackage');
            $c->where(array(
                'workspace' => 1,
                "(SELECT
                    `signature`
                  FROM {$modx->getTableName('transport.modTransportPackage')} AS `latestPackage`
                  WHERE `latestPackage`.`package_name` = `modTransportPackage`.`package_name`
                  ORDER BY
                     `latestPackage`.`version_major` DESC,
                     `latestPackage`.`version_minor` DESC,
                     `latestPackage`.`version_patch` DESC,
                     IF(`release` = '' OR `release` = 'ga' OR `release` = 'pl','z',`release`) DESC,
                     `latestPackage`.`release_index` DESC
                  LIMIT 1,1) = `modTransportPackage`.`signature`",
            ));
            $c->where(array(
                'modTransportPackage.package_name' => 'filedownloadr',
                'installed:IS NOT' => null
            ));

            /** @var modTransportPackage $oldPackage */
            $oldPackage = $modx->getObject('transport.modTransportPackage', $c);

            $modelPath = $modx->getOption('filedownloadr.core_path', null, $modx->getOption('core_path') . 'components/filedownloadr/') . 'model/';
            $modx->addPackage('filedownloadr', $modelPath);

            if ($oldPackage) {
                if ($oldPackage->compareVersion('2.0.0-beta1', '>') &&
                    $oldPackage->compareVersion('2.0.0-beta1', '!=')
                ) {
                    $count = (int)$modx->getCount('fdCount');
                    if ($count > 0) {
                        $modx->log(modX::LOG_LEVEL_INFO, "Starting to convert the database...", '', 'FileDownloadR');
                        $split = ceil($count / 1000); // limit
                        for ($index = 0; $index < $split; $index++) {
                            $offset = $index * 10;
                            convertCount($modx, $offset);
                        }
                        $modx->log(modX::LOG_LEVEL_INFO, "Conversion is finished!", '', 'FileDownloadR');
                    }
                }
            }
            break;
    }
}
return true;