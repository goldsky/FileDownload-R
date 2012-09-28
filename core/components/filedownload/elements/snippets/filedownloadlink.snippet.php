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
 * @author      goldsky <goldsky@fastmail.fm> <http://virtudraft.com>
 * @package     filedownload
 * @subpackage  filedownloadlink snippet
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

$configs = array();

$configs['encoding'] = $modx->getOption('encoding', $scriptProperties, 'UTF-8');
header('Content-Type: text/html; charset=' . $configs['encoding']);
mb_internal_encoding($configs['encoding']);

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
$configs['getFile'] = $modx->getOption('getFile', $scriptProperties);
/**
 * for Output Filter Modifier
 * @link http://rtfm.modx.com/display/revolution20/Custom+Output+Filter+Examples#CustomOutputFilterExamples-CreatingaCustomOutputModifier
 */
if (empty($configs['getFile']) && !empty($scriptProperties['input'])) {
    $configs['getFile'] = $scriptProperties['input'];
} elseif (empty($configs['getFile']) && empty($scriptProperties['input'])) {
    return '<!-- getFile parameter is empty -->';
}
$comma = stristr($configs['getFile'], ',');
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
$configs['chkDesc'] = $modx->getOption('chkDesc', $scriptProperties);
/**
 * The dateFormat parameter will change the format of the date displayed for
 * each file in the output.
 * @options: PHP's date formatting
 * @default: Y-m-d
 * @example: m/d/Y
 * @var string
 * @since ver 1.2.0
 */
$configs['dateFormat'] = $modx->getOption('dateFormat', $scriptProperties, 'Y-m-d');

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
$configs['downloadGroups'] = $modx->getOption('downloadGroups', $scriptProperties);
if (!empty($configs['downloadGroups']) && empty($scriptProperties['userGroups'])) {
    $configs['userGroups'] = $configs['downloadGroups'];
} else {
    $configs['userGroups'] = $modx->getOption('userGroups', $scriptProperties);
}
unset($configs['downloadGroups']);
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
$configs['countDownloads'] = $modx->getOption('countDownloads', $scriptProperties, 1);
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
$configs['imgLocat'] = $modx->getOption('imgLocat', $scriptProperties, 'assets/components/filedownload/img/filetype');
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
$configs['imgTypes'] = $modx->getOption('imgTypes', $scriptProperties);

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
$configs['tpl'] = $modx->getOption('tpl', $scriptProperties, '@CODE: <a href="[[+link]]">[[+filename]]</a> ([[+count]] downloads)');
if (!empty($scriptProperties['tplCode'])) {
    $configs['tpl'] = '@CODE: ' . $scriptProperties['tplCode'];
}

/**
 * Template for forbidden access
 * @options: @BINDINGs
 * @default: @FILE: [[++core_path]]components/filedownload/elements/chunks/tpl-notallowed.chunk.tpl
 * @var string
 * @since ver 2.0.0
 */
$configs['tplNotAllowed'] = $modx->getOption('tplNotAllowed', $scriptProperties, '@FILE: [[++core_path]]components/filedownload/elements/chunks/tpl-notallowed.chunk.tpl');

/**
 * This property will make the list only displays files without their download links.
 * @default: null
 * @var string
 * @since ver 1.2.0
 */
$configs['noDownload'] = $modx->getOption('noDownload', $scriptProperties);
/**
 * Turn on the ajax mode for the script.
 * @options: 1 | 0
 * @default: 0
 * @var bool
 * @since ver 2.0.0
 */
$configs['ajaxMode'] = $modx->getOption('ajaxMode', $scriptProperties);
/**
 * The MODX's resource page id as the Ajax processor file
 * @var int
 * @since ver 2.0.0
 * @subpackage FileDownloadController
 */
$configs['ajaxControllerPage'] = $modx->getOption('ajaxControllerPage', $scriptProperties);
/**
 * The Ajax's element container id
 * @default: file-download
 * @example <div id="file-download"></div>
 * @var string
 * @since ver 2.0.0
 */
$configs['ajaxContainerId'] = $modx->getOption('ajaxContainerId', $scriptProperties, 'file-download');
/**
 * FileDownload's Javascript file for the page header
 * @default: assets/components/filedownload/js/fd.js
 * @var string
 * @since ver 2.0.0
 */
$configs['fileJs'] = $modx->getOption('fileJs', $scriptProperties
        , $modx->getOption('assets_url') . 'components/filedownload/js/fd.js');
/**
 * FileDownload's Cascading Style Sheet file for the page header
 * @default: assets/components/filedownload/css/fd.css
 * @var string
 * @since ver 2.0.0
 */
$configs['fileCss'] = $modx->getOption('fileCss', $scriptProperties
        , $modx->getOption('assets_url') . 'components/filedownload/css/fd.css');
/**
 * This text will be added to the file's hashed link to disguise the direct path
 * @default: FileDownload
 * @var string
 * @since ver 2.0.0
 */
$configs['saltText'] = $modx->getOption('saltText', $scriptProperties);
/**
 * This parameter provides the direct link
 * @default: 0
 * @var string
 * @since ver 2.0.0
 */
$configs['directLink'] = $modx->getOption('directLink', $scriptProperties, 0);
/**
 * This is a given ID to the snippet to deal with multiple snippet calls and
 * &browseDirectories altogether
 * @default: null
 * @var string
 */
$configs['plugins'] = $modx->getOption('plugins', $scriptProperties);

/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
////                                                                         ////
////                   Here goes the MODX Revolution's part                  ////
////                                                                         ////
/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////

array_walk($configs, create_function('&$val', 'if (!is_array($val)) $val = trim($val);'));

$fdl = $modx->getService('fdl'
        , 'FileDownload'
        , $modx->getOption('core_path') . 'components/filedownload/models/filedownload/'
);

if (!($fdl instanceof FileDownload))
    return 'instanceof error.';

$fdl->setConfigs($configs);

if (!$fdl->isAllowed()) {
    return $fdl->parseTpl($configs['tplNotAllowed'], array());
}

if ($configs['fileCss'] !== 'disabled') {
    $modx->regClientCSS($fdl->replacePropPhs($configs['fileCss']));
}

if ($configs['ajaxMode'] && !empty($configs['ajaxControllerPage'])) {
    // require dojo
    if (!file_exists(realpath(MODX_BASE_PATH . 'assets/components/filedownload/js/dojo/dojo.js'))) {
        return 'dojo.js is required.';
    }
    $modx->regClientStartupScript($fdl->configs['jsUrl'] . 'dojo/dojo.js');
    if ($configs['fileJs'] !== 'disabled') {
        $modx->regClientStartupScript($fdl->replacePropPhs($configs['fileJs']));
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
    $page = $modx->makeUrl($modx->resource->get('id'), '', '', 'full');
    if ($baseRef !== $page) {
        return $modx->sendUnauthorizedPage();
    }
    $sanitizedGets = $modx->sanitize($_GET);
}

if (empty($configs['downloadByOther'])) {
    if (!empty($_GET['fdlfile'])) {
        if (!$fdl->checkHash($modx->context->key, $sanitizedGets['fdlfile']))
            return;
        $downloadFile = $fdl->downloadFile($sanitizedGets['fdlfile']);
        if (!$downloadFile) {
            return;
        }
        return;
    }
}

$contents = $fdl->getContents();

if (!$contents) {
    return;
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
        $output = $fdl->parseTpl($configs['tpl'], $contents['file'][0]);
    }
} elseif (!empty($toArray)) {
    $output = '<pre>';
    $output .= print_r($contents['file'][0], true);
    $output .= '</pre>';
} else {
    $output = $fdl->parseTpl($configs['tpl'], $contents['file'][0]);
}

if (!empty($toPlaceholder)) {
    $modx->setPlaceholder($toPlaceholder, $output);
    return;
}

return $output;