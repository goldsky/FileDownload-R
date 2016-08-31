<?php

/**
 * The snippet for the FileDownloadR package for MODX Revolution
 * This is the conversion of the original FileDownloadR snippet for MODX
 * Evolution, which was originally created by Kyle Jaebker, and converted by
 * goldsky.
 * The main parameters are taken from that version so any conversion can be done
 * smoothly.
 *
 * FileDownloadR is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * FileDownloadR is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * FileDownloadR; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @author      goldsky <goldsky@virtudraft.com>
 * @package     filedownload
 * @subpackage  filedownloadlink snippet
 *
 * @var modX $modx
 * @var array $scriptProperties
 */
$assetsUrl = $modx->getOption('filedownloadr.assets_url', $scriptProperties, $modx->getOption('assets_url') . 'components/filedownloadr/', true);

$scriptProperties['encoding'] = $modx->getOption('encoding', $scriptProperties, 'UTF-8', true);
header('Content-Type: text/html; charset=' . $scriptProperties['encoding']);
mb_internal_encoding($scriptProperties['encoding']);

/////////////////////////////////////////////////////////////////////////////////
//                               Main Parameters                               //
/////////////////////////////////////////////////////////////////////////////////
/**
 * The getFile parameter will make the snippet output only the file specified.
 * The getDir parameter is still required and getFile should be a file inside
 * of the directory.
 * This allows for use of the download script and download counting with a
 * single file.
 * @options: string
 * @default: null
 * @var string
 */
$scriptProperties['getFile'] = $modx->getOption('getFile', $scriptProperties, '', true);
/**
 * for Output Filter Modifier
 * @link http://rtfm.modx.com/display/revolution20/Custom+Output+Filter+Examples#CustomOutputFilterExamples-CreatingaCustomOutputModifier
 */
if (empty($scriptProperties['getFile']) && !empty($scriptProperties['input'])) {
    $scriptProperties['getFile'] = $scriptProperties['input'];
} elseif (empty($scriptProperties['getFile']) && empty($scriptProperties['input'])) {
    return '<!-- getFile parameter is empty -->';
}
$comma = stristr($scriptProperties['getFile'], ',');
if ($comma) {
    return '<!-- getFile parameter should be only one file -->';
}

/**
 * This allows descriptions to be added to the file listing included in a chunk.
 * All of the files and descriptions should be listed in the chunk using the
 * following format: path to file/filename|description||
 * @options: the name of a chunk
 * @default: null
 * @example:
 *     chunk's name: fileDescription
 *     chunk content:
 *         assets/files/test.pdf|This is a test pdf. It shows report stuff.||
 *         assets/images/options.gif|These are the options available to you.||
 * @var string
 * @since ver 1.2.0
 */
$scriptProperties['chkDesc'] = $modx->getOption('chkDesc', $scriptProperties, '', true);
/**
 * The dateFormat parameter will change the format of the date displayed for
 * each file in the output.
 * @options: PHP's date formatting
 * @default: Y-m-d
 * @example: m/d/Y
 * @var string
 * @since ver 1.2.0
 */
$scriptProperties['dateFormat'] = $modx->getOption('dateFormat', $scriptProperties, 'Y-m-d', true);

/////////////////////////////////////////////////////////////////////////////////
//                                 Permissions                                 //
/////////////////////////////////////////////////////////////////////////////////

/**
 * This will make the download link active for users that belong to the specified
 * groups. Multiple groups can be specified by using a comma delimited list.
 * @options: comma delimited list of User groups
 * @default: null
 * @example: Administrator, Registered Member
 * @var string
 * @since ver 1.2.0
 * @deprecated deprecated since 2.0.0. Use 'userGroups' instead.
 */
$scriptProperties['downloadGroups'] = $modx->getOption('downloadGroups', $scriptProperties, '', true);
if (!empty($scriptProperties['downloadGroups']) && empty($scriptProperties['userGroups'])) {
    $scriptProperties['userGroups'] = $scriptProperties['downloadGroups'];
} else {
    $scriptProperties['userGroups'] = $modx->getOption('userGroups', $scriptProperties, '', true);
}
unset($scriptProperties['downloadGroups']);

/////////////////////////////////////////////////////////////////////////////////
//                              Download Counting                              //
/////////////////////////////////////////////////////////////////////////////////
/**
 * With the countDownloads parameter set to 1, everytime a user downloads a file
 * it will be tracked in a database table.
 * @options: 1 | 0
 * @default: 1
 * @var bool
 * @since ver 1.2.0
 */
$scriptProperties['countDownloads'] = $modx->getOption('countDownloads', $scriptProperties, 1, true);
/////////////////////////////////////////////////////////////////////////////////
//                                   Images                                    //
/////////////////////////////////////////////////////////////////////////////////
/**
 * Path to the images to associate with each file extension.
 * The images will be outputted with [[+fd.image]] placeholder.
 * @options: path to images
 * @default: {assets_url}components/filedownloadr/img/filetypes/
 * @example: assets/images/icons
 * @var string
 * @since ver 1.2.0
 */
$scriptProperties['imgLocat'] = $modx->getOption('imgLocat', $scriptProperties, $assetsUrl . 'img/filetypes/', true);

/**
 * This allows for associations between file extensions and an image.
 * The information on these associations should be put into a chunk similar to
 * the example below. Associations should be in a comma delimited list with an
 * equal sign between the extension and the image name.
 * The directory extension is used for getting the image to associate with a
 * directory.
 * The default extension is applied to all files with extensions not specified
 * in the chunk.
 * @options: name of a chunk
 * @default: null
 * @example:
 *     chunk's name: fdImages
 *     chunk content:
 *          jpg     = image.png,
 *          png     = image.png,
 *          gif     = image.png,
 *          php     = document-php.png,
 *          js      = document-code.png,
 *          pdf     = document-pdf.png,
 *          txt     = document-text.png,
 *          zip     = folder-zipper.png,
 *          html    = globe.png,
 *          xls     = document-excel.png,
 *          xlsx    = document-excel.png,
 *          doc     = document-word.png,
 *          docx    = document-word.png,
 *          mdb     = document-access.png,
 *          ppt     = document-powerpoint.png,
 *          pptx    = document-powerpoint.png,
 *          pps     = slide-powerpoint.png,
 *          ppsx    = slide-powerpoint.png,
 *          mov     = film.png,
 *          avi     = film.png,
 *          mp3     = music.png,
 *          wav     = music.png,
 *          flv     = document-flash-movie.png,
 *          dir     = folder.png,
 *          default = document.png
 * @var string
 * @since ver 1.2.0
 */
$scriptProperties['imgTypes'] = $modx->getOption('imgTypes', $scriptProperties, 'fdimages', true);

/////////////////////////////////////////////////////////////////////////////////
//                            Templates & Styles                               //
/////////////////////////////////////////////////////////////////////////////////
/**
 * Template code to be returned
 * @options: code
 * @default: <a href="[[+link]]">[[+filename]]</a>
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['tpl'] = $modx->getOption('tpl', $scriptProperties, '@CODE: <a href="[[+link]]">[[+filename]]</a> ([[+count]] downloads)');
if (!empty($scriptProperties['tplCode'])) {
    $scriptProperties['tpl'] = '@CODE: ' . $scriptProperties['tplCode'];
}

/**
 * Template for forbidden access
 * @options: chunk's name
 * @default: tpl-notallowed
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['tplNotAllowed'] = $modx->getOption('tplNotAllowed', $scriptProperties, 'tpl-notallowed');

/**
 * This property will make the list only displays files without their download links.
 * @default: null
 * @var string
 * @since ver 1.2.0
 */
$scriptProperties['noDownload'] = $modx->getOption('noDownload', $scriptProperties, '', true);
/**
 * Turn on the ajax mode for the script.
 * @options: 1 | 0
 * @default: 0
 * @var bool
 * @since ver 2.0.0
 */
$scriptProperties['ajaxMode'] = $modx->getOption('ajaxMode', $scriptProperties, '0', true);
/**
 * The MODX's resource page id as the Ajax processor file
 * @var int
 * @since ver 2.0.0
 * @subpackage FileDownloadController
 */
$scriptProperties['ajaxControllerPage'] = $modx->getOption('ajaxControllerPage', $scriptProperties, '', true);
/**
 * The Ajax's element container id
 * @default: file-download
 * @example <div id="file-download"></div>
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['ajaxContainerId'] = $modx->getOption('ajaxContainerId', $scriptProperties, 'file-download', true);
/**
 * FileDownloadR's Javascript file for the page header
 * @default: empty
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['fileJs'] = $modx->getOption('fileJs', $scriptProperties, '', true);
/**
 * FileDownloadR's Cascading Style Sheet file for the page header
 * @default: {assets_url}components/filedownloadr/css/fd.css
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['fileCss'] = $modx->getOption('fileCss', $scriptProperties, $assetsUrl . 'css/fd.min.css', true);

/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
////                                                                         ////
////                   Here goes the MODX Revolution's part                  ////
////                                                                         ////
/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////

/**
 * This text will be added to the file's hashed link to disguise the direct path
 * @default: FileDownloadR
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['saltText'] = $modx->getOption('saltText', $scriptProperties, 'FileDownloadR', true);
/**
 * This parameter provides the direct link
 * @default: 0
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['directLink'] = $modx->getOption('directLink', $scriptProperties, 0, true);
/**
 * This is a given ID to the snippet to deal with multiple snippet calls and
 * &browseDirectories altogether
 * @default: null
 * @var string
 */
$scriptProperties['fdlid'] = $modx->getOption('fdlid', $scriptProperties, '', true);
/**
 * Attach plugin to the output
 * @default: null
 * @var string
 */
$scriptProperties['plugins'] = $modx->getOption('plugins', $scriptProperties, '', true);

/**
 * prefix for the placeholders
 * @default: fd.
 * @var string
 */
$scriptProperties['prefix'] = $modx->getOption('prefix', $scriptProperties, 'fd.', true);

/**
 * Use IP location or not
 * @default: false
 * @var boolean
 */
$scriptProperties['useGeolocation'] = (boolean)$modx->getOption('useGeolocation', $scriptProperties, $modx->getOption('filedownloadr.use_geolocation', null, false), true);

/**
 * API key of IPInfoDB.com
 * @default: ''
 * @var string
 */
$scriptProperties['geoApiKey'] = $modx->getOption('geoApiKey', $scriptProperties, $modx->getOption('filedownloadr.ipinfodb_api_key', $scriptProperties, '', true));

array_walk($scriptProperties, create_function('&$val', 'if (!is_array($val)) $val = trim($val);'));

$corePath = $modx->getOption('filedownloadr.core_path', null, $modx->getOption('core_path') . 'components/filedownloadr/');
$fdl = $modx->getService('filedownloadr', 'FileDownloadR', $corePath . 'model/filedownloadr/', array_merge($scriptProperties, array(
    'core_path' => $corePath
)));

if (!($fdl instanceof FileDownloadR)) {
    return 'FileDownloadR instanceof error.';
}

$fdl->setConfigs($scriptProperties);

if (!$fdl->isAllowed()) {
    return $fdl->parseTpl($scriptProperties['tplNotAllowed'], array());
}

if ($scriptProperties['fileCss'] !== 'disabled') {
    $modx->regClientCSS($fdl->replacePropPhs($scriptProperties['fileCss']));
}

if ($scriptProperties['ajaxMode'] && !empty($scriptProperties['ajaxControllerPage'])) {
    if ($scriptProperties['fileJs'] !== 'disabled') {
        $modx->regClientStartupScript($fdl->replacePropPhs($scriptProperties['fileJs']));
    }
}

if (!empty($_GET['fdlfile'])) {
    $ref = $_SERVER['HTTP_REFERER'];
    // deal with multiple snippets which have &browseDirectories
    $xRef = @explode('?', $ref);
    $queries = array();
    parse_str($xRef[1], $queries);
    if (!empty($queries['id'])) {
        // non FURL
        $baseRef = $xRef[0] . '?id=' . $queries['id'];
    } else {
        $baseRef = $xRef[0];
    }
    $baseRef = urldecode($baseRef);
    $page = $modx->makeUrl($modx->resource->get('id'), '', '', 'full');
    /**
     * check referrer and the page
     */
    if ($baseRef !== $page) {
        return $modx->sendUnauthorizedPage();
    }
}

if (empty($scriptProperties['downloadByOther'])) {
    if (!empty($_GET['fdlfile'])) {
        $sanitizedGets = $modx->sanitize($_GET);
        if (!$fdl->checkHash($modx->context->key, $sanitizedGets['fdlfile'])) {
            return;
        }
        $downloadFile = $fdl->downloadFile($sanitizedGets['fdlfile']);
        if (!$downloadFile) {
            return;
        }
        // simply terminate, because this is a downloading state
        die();
    }
}

$contents = $fdl->getContents();

if (empty($contents)) {
    return;
}

$fileInfos = $contents['file'][0];
$filePhs = array();
foreach ($fileInfos as $k => $v) {
    $filePhs[$scriptProperties['prefix'] . $k] = $v;
}
// fallback without prefix
$fileInfos = array_merge($fileInfos, $filePhs);

/**
 * for Output Filter Modifier
 * @link http://rtfm.modx.com/display/revolution20/Custom+Output+Filter+Examples#CustomOutputFilterExamples-CreatingaCustomOutputModifier
 */
if (!empty($scriptProperties['input'])) {
    $output = $fileInfos[$scriptProperties['options']];
    if (empty($output)
        && !is_numeric($output) // avoid 0 (zero) of the download counting.
    ) {
        $output = $fdl->parseTpl($scriptProperties['tpl'], $fileInfos);
    }
} elseif (!empty($toArray)) {
    $output = '<pre>' . print_r($fileInfos, true) . '</pre>';
} else {
    $output = $fdl->parseTpl($scriptProperties['tpl'], $fileInfos);
}

if (!empty($toPlaceholder)) {
    $modx->setPlaceholder($toPlaceholder, $output);
    return '';
}
return $output;