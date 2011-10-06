<?php

/**
 * The snippet for the FileDownload package for MODX Revolution
 * This is the conversion of the original FileDownload snippet for MODX
 * Evolution, which was originally created by Kyle Jaebker, and converted by
 * goldsky.
 * The main parameters are taken from that version so any conversion can be done
 * smoothly.
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
 * @author goldsky <goldsky@fastmail.fm> <http://virtudraft.com>
 * @package filedownload
 */
if (get_magic_quotes_gpc()) {
    if (!function_exists('stripslashes_gpc')) {

        function stripslashes_gpc(&$value) {
            $value = stripslashes($value);
        }

    }
    array_walk_recursive($_GET, 'stripslashes_gpc');
    array_walk_recursive($_POST, 'stripslashes_gpc');
    array_walk_recursive($_COOKIE, 'stripslashes_gpc');
    array_walk_recursive($_REQUEST, 'stripslashes_gpc');
}

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
$scriptProperties['getFile'] = $modx->getOption('getFile', $scriptProperties);
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
 *         assets/snippets/filedownload/test.pdf|This is a test pdf. It shows report stuff.||
 *         assets/snippets/filedownload/options.gif|These are the options available to you.||
 * @var string
 * @since ver 1.2.0
 */
$scriptProperties['chkDesc'] = $modx->getOption('chkDesc', $scriptProperties);
/**
 * The dateFormat parameter will change the format of the date displayed for
 * each file in the output.
 * @options: PHP's date formatting
 * @default: Y-m-d
 * @example: m/d/Y
 * @var string
 * @since ver 1.2.0
 */
$scriptProperties['dateFormat'] = $modx->getOption('dateFormat', $scriptProperties, 'Y-m-d');

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
$scriptProperties['downloadGroups'] = $modx->getOption('downloadGroups', $scriptProperties);
if (!empty($scriptProperties['downloadGroups']) && empty($scriptProperties['userGroups'])) {
    $scriptProperties['userGroups'] = $scriptProperties['downloadGroups'];
} else {
    $scriptProperties['userGroups'] = $modx->getOption('userGroups', $scriptProperties);
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
$scriptProperties['countDownloads'] = $modx->getOption('countDownloads', $scriptProperties, 1);
/////////////////////////////////////////////////////////////////////////////////
//                                   Images                                    //
/////////////////////////////////////////////////////////////////////////////////
/**
 * Path to the images to associate with each file extension.
 * The images will be outputted with [+fd.image+] placeholder.
 * @options: path to images
 * @default: assets/components/filedownload/img/filetype
 * @example: assets/images/icons
 * @var string
 * @since ver 1.2.0
 */
$scriptProperties['imgLocat'] = $modx->getOption('imgLocat', $scriptProperties, 'assets/components/filedownload/img/filetype');
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
 *     chunk content:jpg     = image.png,
 *     png     = image.png,
 *     gif     = image.png,
 *     php     = document-php.png,
 *     js      = document-code.png,
 *     pdf     = document-pdf.png,
 *     txt     = document-text.png,
 *     zip     = folder-zipper.png,
 *     html    = globe.png,
 *     xls     = document-excel.png,
 *     xlsx    = document-excel.png,
 *     doc     = document-word.png,
 *     docx    = document-word.png,
 *     mdb     = document-access.png,
 *     ppt     = document-powerpoint.png,
 *     pptx    = document-powerpoint.png,
 *     pps     = slide-powerpoint.png,
 *     ppsx    = slide-powerpoint.png,
 *     mov     = film.png,
 *     avi     = film.png,
 *     mp3     = music.png,
 *     wav     = music.png,
 *     flv     = document-flash-movie.png,
 *     dir     = folder.png,
 *     default = document.png
 * @var string
 * @since ver 1.2.0
 */
$scriptProperties['imgTypes'] = $modx->getOption('imgTypes', $scriptProperties);

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
$scriptProperties['tplCode'] = $modx->getOption('tplCode', $scriptProperties, '<a href="[[+link]]">[[+filename]]</a>');
/**
 * This text will be added to the file's hashed link to disguise the direct path
 * @default: FileDownload
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['saltText'] = $modx->getOption('saltText', $scriptProperties);
/**
 * This parameter provides the direct link
 * @default: 0
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['directLink'] = $modx->getOption('directLink', $scriptProperties, 0);

/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
////                                                                         ////
////                   Here goes the MODX Revolution's part                  ////
////                                                                         ////
/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////

array_walk($scriptProperties, create_function('&$val', 'if (!is_array($val)) $val = trim($val);'));

$fdl = $modx->getService('fdl'
        , 'FileDownload'
        , $modx->getOption('core_path') . 'components/filedownload/models/filedownload/'
        );

if (!($fdl instanceof FileDownload))
    return 'instanceof error.';

$fdl->setConfigs($scriptProperties);

if (!$fdl->isAllowed()) {
    return '';
}

if ($scriptProperties['fileCss'] !== 'disabled') {
    $modx->regClientCSS($fdl->replacePropPhs($scriptProperties['fileCss']));
}

if ($scriptProperties['ajaxMode'] && !empty($scriptProperties['ajaxControllerPage'])) {
    // require dojo
    if (!file_exists(realpath(MODX_BASE_PATH . 'assets/components/filedownload/js/dojo/dojo.js'))) {
        return 'dojo.js is required.';
    }
    $modx->regClientStartupScript($fdl->config['jsUrl'] . 'dojo/dojo.js');
    if ($scriptProperties['fileJs'] !== 'disabled') {
        $modx->regClientStartupScript($fdl->replacePropPhs($scriptProperties['fileJs']));
    }
}

if (!empty($_GET)) {
    $sanitizedGets = $modx->sanitize($_GET);
}
if (!empty($_GET['fdlfile'])) {
    if (!$fdl->checkHash($modx->context->key, $sanitizedGets['fdlfile']))
        return FALSE;
    $downloadFile = $fdl->downloadFile($sanitizedGets['fdlfile']);
    if (!$downloadFile) {
        return '';
    }
    return '';
}

$contents = $fdl->getContents();

if (!$contents) {
    return '';
}

$output = '';

/**
 * for Output Filter Modifier
 * @link http://rtfm.modx.com/display/revolution20/Custom+Output+Filter+Examples#CustomOutputFilterExamples-CreatingaCustomOutputModifier
 */
if (!empty($scriptProperties['input'])) {
    $output = $contents['file'][0][$scriptProperties['options']];
    if (empty($output)
            && !is_numeric($output) // avoid 0 (zero) of the download counting.
    ) {
        $output = $fdl->parseTplCode($scriptProperties['tplCode'], $contents['file'][0]);
    }
} elseif (!empty($toArray)) {
    $output = '<pre>';
    $output .= print_r($contents['file'][0], true);
    $output .= '</pre>';
} elseif (!empty($toPlaceholder)) {
    return $modx->setPlaceholder($toPlaceholder, $fdl->parseTplCode($scriptProperties['tplCode'], $contents['file'][0]));
} else {
    $output = $fdl->parseTplCode($scriptProperties['tplCode'], $contents['file'][0]);
}

return $output;