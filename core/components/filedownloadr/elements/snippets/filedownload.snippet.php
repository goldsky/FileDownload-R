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
 * @author      Kyle Jaebker <http://muddydogpaws.com>
 * @author      goldsky <goldsky@virtudraft.com>
 * @package     filedownload
 * @subpackage  filedownload snippet
 */
$scriptProperties['encoding'] = $modx->getOption('encoding', $scriptProperties, 'UTF-8');
header('Content-Type: text/html; charset=' . $scriptProperties['encoding']);
mb_internal_encoding($scriptProperties['encoding']);

/////////////////////////////////////////////////////////////////////////////////
//                               Main Parameters                               //
/////////////////////////////////////////////////////////////////////////////////
/**
 * This is used to specify which directories to display with the snippet.
 * Multiple directories can be specified by seperating them with a comma.
 * When specifying multiple directories the directory browsing functionality is
 * no longer available.
 * @options: comma delimited list of directories
 * @default: null
 * @example: assets/snippets/filedownload
 * @var string
 * @since ver 1.2.0
 */
$scriptProperties['getDir'] = $modx->getOption('getDir', $scriptProperties);
$scriptProperties['origDir'] = $modx->getOption('getDir', $scriptProperties); // getDir will be overridden by &_GET['fdldir'] in setDirProp()

/**
 * The getFile parameter will make the snippet output only the file specified.
 * The getDir parameter is still required and getFile should be a file inside
 * of the directory.
 * This allows for use of the download script and download counting with a
 * single file.
 * @options: string
 * @default: null
 * @example: filedownload.php
 * @var string
 * @since ver 1.2.0
 */
$scriptProperties['getFile'] = $modx->getOption('getFile', $scriptProperties);

if (empty($scriptProperties['getDir']) && empty($scriptProperties['getFile'])) {
    return '<!-- FileDownloadR parameters are empty -->';
}

/**
 * This allows users to view subdirectories of the directory specified with the
 * getDir parameter. When using this feature the following templates get used:
 * path & directory.
 * @options: 1 | 0
 * @default: 0
 * @var bool
 * @since ver 1.2.0
 */
$scriptProperties['browseDirectories'] = $modx->getOption('browseDirectories', $scriptProperties, 0);
// typo fall back
$scriptProperties['browseDirectory'] = !empty($scriptProperties['browseDirectory']) ?
        $scriptProperties['browseDirectory'] :
        $scriptProperties['browseDirectories'];
/**
 * If multiple directories are specified in the getDir parameter, this property
 * will group the files by each directory.
 * When grouped by directory, the directory template is used to output the path
 * above each group.
 * @options: 1 | 0
 * @default: 0
 * @var bool
 * @since ver 1.2.0
 */
$scriptProperties['groupByDirectory'] = $modx->getOption('groupByDirectory', $scriptProperties, 0);
// typo fall back
$scriptProperties['groupByDirectories'] = !empty($scriptProperties['groupByDirectories']) ?
        $scriptProperties['groupByDirectories'] :
        $scriptProperties['groupByDirectory'];
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
$scriptProperties['chkDesc'] = $modx->getOption('chkDesc', $scriptProperties);
/**
 * @var string
 * @since ver 1.2.0
 * @deprecated deprecated since version 2.0.0, use sortBy instead
 */
// $scriptProperties['userSort'] = $modx->getOption('userSort', $scriptProperties);
/**
 * Sort ordering.
 * This allows the files to be sorted by all of the fields listed below.
 * To sort by multiple fields use a comma delimited list.
 * When using the directory browsing feature the files will be sorted by type
 * first, this will put the directories first in the list. When multiple folders
 * are specified and the group by directory feature is used; the files are
 * sorted by path first to keep the files in order by directory.
 * @options: filename | extension | path | size | sizetext | type | date | description | count
 * @default:
 *     filename;
 *     if &browseDirectories=`1`, sort by: type,filename;
 *     if &groupByDirectory=`1`, sort by: path,filename;
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['sortBy'] = $modx->getOption('sortBy', $scriptProperties, 'filename');
/**
 * Case sensitive option for sorting
 * @options: 1 | 0
 * @default: 0
 * @var bool
 * @since ver 2.0.0
 */
$scriptProperties['sortByCaseSensitive'] = $modx->getOption('sortByCaseSensitive', $scriptProperties, 0);
/**
 * Sort files in ascending or descending order.
 * @options: asc | desc
 * @default: asc
 * @var string
 * @since ver 1.2.0
 */
$scriptProperties['sortOrder'] = $modx->getOption('sortOrder', $scriptProperties, 'asc');
/**
 * Sort order option by a natural order
 * @options: 1 | 0
 * @default: 1
 * @var bool
 * @since ver 2.0.0
 */
$scriptProperties['sortOrderNatural'] = $modx->getOption('sortOrderNatural', $scriptProperties, 1);
/**
 * This will limit the inclusion files displayed to files with a valid extension
 * from the list.
 * @options: comma delimited list of file extensions
 * @default: null
 * @example: zip,php,txt
 * @var string
 * @since ver 1.2.0
 * @deprecated deprecated since 2.0.0. Use 'extShown' instead.
 */
$scriptProperties['showExt'] = $modx->getOption('showExt', $scriptProperties);
if (!empty($scriptProperties['showExt']) && empty($scriptProperties['extShown'])) {
    $scriptProperties['extShown'] = $scriptProperties['showExt'];
} else {
    $scriptProperties['extShown'] = $modx->getOption('extShown', $scriptProperties);
}
unset($scriptProperties['showExt']);
/**
 * This will exclude the files displayed to files with a valid extension from
 * the list.
 * @options: comma delimited list of file extensions
 * @default: null
 * @example: zip,php,txt
 * @var string
 * @since ver 1.2.0
 * @deprecated deprecated since 2.0.0. Use 'extHidden' instead.
 */
$scriptProperties['hideExt'] = $modx->getOption('hideExt', $scriptProperties);
if (!empty($scriptProperties['hideExt']) && empty($scriptProperties['extHidden'])) {
    $scriptProperties['extHidden'] = $scriptProperties['hideExt'];
} else {
    $scriptProperties['extHidden'] = $modx->getOption('extHidden', $scriptProperties);
}
unset($scriptProperties['hideExt']);
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
 * The images will be outputted with [[+fd.image]] placeholder.
 * @options: path to images
 * @default: assets/components/filedownloadr/img/filetypes/
 * @example: assets/images/icons
 * @var string
 * @since ver 1.2.0
 */
$scriptProperties['imgLocat'] = $modx->getOption('imgLocat', $scriptProperties, 'assets/components/filedownloadr/img/filetypes/');
$scriptProperties['imgTypeUrl'] = $scriptProperties['imgLocat'];

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
$scriptProperties['imgTypes'] = $modx->getOption('imgTypes', $scriptProperties, 'fdimages');

/////////////////////////////////////////////////////////////////////////////////
//                            Templates & Styles                               //
/////////////////////////////////////////////////////////////////////////////////
/**
 * This is the directory row template (chunk/file) if it is accessible
 * @options: chunk's name
 * @default: tpl-dir
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['tplDir'] = $modx->getOption('tplDir', $scriptProperties, 'tpl-row-dir');
/**
 * This is the file row template (chunk/file)
 * @options: chunk's name
 * @default: tpl-file
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['tplFile'] = $modx->getOption('tplFile', $scriptProperties, 'tpl-row-file');
/**
 * This is the file row template (chunk/file)
 * @options: chunk's name
 * @default: tpl-file
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['tplGroupDir'] = $modx->getOption('tplGroupDir', $scriptProperties, 'tpl-group-dir');
/**
 * This is the container template (chunk/file) of all of the snippet's results
 * @options: chunk's name
 * @default: tpl-wrapper
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['tplWrapper'] = $modx->getOption('tplWrapper', $scriptProperties, 'tpl-wrapper');
/**
 * This is the container template for folders
 * @options: chunk's name, or empty to disable
 * @default: tpl-wrapper-dir
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['tplWrapperDir'] = $modx->getOption('tplWrapperDir', $scriptProperties);
/**
 * This is the container template for files
 * @options: chunk's name, or empty to disable
 * @default: tpl-wrapper-dir
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['tplWrapperFile'] = $modx->getOption('tplWrapperFile', $scriptProperties);
/**
 * index.html file/chunk to hide the download folders
 * @options: chunk's name
 * @default: tpl-index
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['tplIndex'] = $modx->getOption('tplIndex', $scriptProperties, 'tpl-index');

/**
 * Template for forbidden access
 * @options: @BINDINGs
 * @default: @FILE: [[++core_path]]components/filedownloadr/elements/chunks/tpl-notallowed.chunk.tpl
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['tplNotAllowed'] = $modx->getOption('tplNotAllowed', $scriptProperties, '@FILE: [[++core_path]]components/filedownloadr/elements/chunks/tpl-notallowed.chunk.tpl');

/**
 * This specifies the class that will be applied to every other file/directory so
 * a ledger look can be styled.
 * @options: css class name
 * @default: fd-alt
 * @var string
 * @since ver 1.2.0
 * @deprecated deprecated since 2.0.0. Use 'cssAltRow' instead.
 */
$scriptProperties['altCss'] = $modx->getOption('altCss', $scriptProperties);
if (!empty($scriptProperties['altCss']) && empty($scriptProperties['cssAltRow'])) {
    $scriptProperties['cssAltRow'] = $scriptProperties['altCss'];
} else {
    $scriptProperties['cssAltRow'] = $modx->getOption('cssAltRow', $scriptProperties);
}
unset($scriptProperties['altCss']);

/**
 * This specifies the class that will be applied to the first directory.
 * @options: css class name
 * @default: fd-firstDir
 * @var string
 * @since ver 1.2.0
 * @deprecated deprecated since 2.0.0. Use 'cssFirstDir' instead.
 */
$scriptProperties['firstFolderCss'] = $modx->getOption('firstFolderCss', $scriptProperties);
if (!empty($scriptProperties['firstFolderCss']) && empty($scriptProperties['cssFirstDir'])) {
    $scriptProperties['cssFirstDir'] = $scriptProperties['firstFolderCss'];
} else {
    $scriptProperties['cssFirstDir'] = $modx->getOption('cssFirstDir', $scriptProperties);
}
unset($scriptProperties['firstFolderCss']);
/**
 * This specified the class that will be applied to the last directory.
 * @options: css class name
 * @default: fd-lastDir
 * @var string
 * @since ver 1.2.0
 * @deprecated deprecated since 2.0.0. Use 'cssLastDir' instead.
 */
$scriptProperties['lastFolderCss'] = $modx->getOption('lastFolderCss', $scriptProperties);
if (!empty($scriptProperties['lastFolderCss']) && empty($scriptProperties['cssLastDir'])) {
    $scriptProperties['cssLastDir'] = $scriptProperties['lastFolderCss'];
} else {
    $scriptProperties['cssLastDir'] = $modx->getOption('cssLastDir', $scriptProperties);
}
unset($scriptProperties['lastFolderCss']);
/**
 * This specified the class that will be applied to the first file.
 * @options: css class name
 * @default: fd-firstFile
 * @var string
 * @since ver 1.2.0
 * @deprecated deprecated since 2.0.0. Use 'cssFirstFile' instead.
 */
$scriptProperties['firstFileCss'] = $modx->getOption('firstFileCss', $scriptProperties);
if (!empty($scriptProperties['firstFileCss']) && empty($scriptProperties['cssFirstFile'])) {
    $scriptProperties['cssFirstFile'] = $scriptProperties['firstFileCss'];
} else {
    $scriptProperties['cssFirstFile'] = $modx->getOption('cssFirstFile', $scriptProperties);
}
unset($scriptProperties['firstFileCss']);
/**
 * This specifies the class that will be applied to the last file.
 * @options: css class name
 * @default: fd-lastFile
 * @var string
 * @since ver 1.2.0
 * @deprecated deprecated since 2.0.0. Use 'cssLastFile' instead.
 */
$scriptProperties['lastFileCss'] = $modx->getOption('lastFileCss', $scriptProperties);
if (!empty($scriptProperties['lastFileCss']) && empty($scriptProperties['cssLastFile'])) {
    $scriptProperties['cssLastFile'] = $scriptProperties['lastFileCss'];
} else {
    $scriptProperties['cssLastFile'] = $modx->getOption('cssLastFile', $scriptProperties);
}
unset($scriptProperties['lastFileCss']);
/**
 * This specifies the class that will be applied to all folders.
 * @options: css class name
 * @default: fd-dir
 * @var string
 * @since ver 1.2.0
 * @deprecated deprecated since 2.0.0. Use 'cssDir' instead.
 */
$scriptProperties['folderCss'] = $modx->getOption('folderCss', $scriptProperties);
if (!empty($scriptProperties['folderCss']) && empty($scriptProperties['cssDir'])) {
    $scriptProperties['cssDir'] = $scriptProperties['folderCss'];
} else {
    $scriptProperties['cssDir'] = $modx->getOption('cssDir', $scriptProperties);
}
unset($scriptProperties['folderCss']);
/**
 * Class name for all files
 * @options: css class name
 * @default: fd-file
 * @var string
 * @since ver 1.2.0
 * @deprecated deprecated since 2.0.0. Use 'cssFile' instead.
 */
$scriptProperties['cssFile'] = $modx->getOption('cssFile', $scriptProperties);
/**
 * This specifies the class that will be applied to the directory for multi-
 * directory grouping.
 * @options: css class name
 * @default: fd-group-dir
 * @var string
 * @since ver 1.2.0
 * @deprecated deprecated since 2.0.0. Use 'cssGroupDir' instead.
 */
$scriptProperties['directoryCss'] = $modx->getOption('directoryCss', $scriptProperties);
if (!empty($scriptProperties['directoryCss']) && empty($scriptProperties['cssGroupDir'])) {
    $scriptProperties['cssGroupDir'] = $scriptProperties['directoryCss'];
} else {
    $scriptProperties['cssGroupDir'] = $modx->getOption('cssGroupDir', $scriptProperties);
}
unset($scriptProperties['directoryCss']);
/**
 * This specifies the class that will be applied to the path when using
 * directory browsing.
 * @options: css class name
 * @default: fd-path
 * @var string
 * @since ver 1.2.0
 * @deprecated deprecated since 2.0.0. Use 'cssPath' instead.
 */
$scriptProperties['pathCss'] = $modx->getOption('pathCss', $scriptProperties);
if (!empty($scriptProperties['pathCss']) && empty($scriptProperties['cssPath'])) {
    $scriptProperties['cssPath'] = $scriptProperties['pathCss'];
} else {
    $scriptProperties['cssPath'] = $modx->getOption('cssPath', $scriptProperties);
}
unset($scriptProperties['pathCss']);
/**
 * With this parameter set to 1, a class will be added to each file according
 * to the file's extension.
 * @options: 1 | 0
 * @default: 0
 * @example: a pdf would get the class: fd-pdf.
 * @var bool
 * @since ver 1.2.0
 * @deprecated deprecated since 2.0.0. Use 'cssExtension' instead.
 */
$scriptProperties['extCss'] = $modx->getOption('extCss', $scriptProperties);
if (!empty($scriptProperties['extCss']) && empty($scriptProperties['cssExtension'])) {
    $scriptProperties['cssExtension'] = $scriptProperties['extCss'];
} else {
    $scriptProperties['cssExtension'] = $modx->getOption('cssExtension', $scriptProperties);
}
unset($scriptProperties['extCss']);
/**
 * Prefix to the above cssExtension class name
 * @default: fd-
 * @example: a pdf would get the class: fd-pdf.
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['cssExtensionPrefix'] = $modx->getOption('cssExtensionPrefix', $scriptProperties, 'fd-');
/**
 * Suffix to the above cssExtension class name
 * @default: null
 * @example: a pdf would get the class: pdfsuffix.
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['cssExtensionSuffix'] = $modx->getOption('cssExtensionSuffix', $scriptProperties);
/**
 * This property will make the list only displays files without their download links.
 * @default: null
 * @var string
 * @since ver 1.2.0
 */
$scriptProperties['noDownload'] = $modx->getOption('noDownload', $scriptProperties);
/**
 * Pass the downloading job to the plugin. This provides flexibility to do
 * conditional statements inside the plugins, or initiate the downloading using
 * AJAX
 * @options: 1 | 0
 * @default: 0
 * @var bool
 * @since ver 2.0.0
 */
$scriptProperties['downloadByOther'] = $modx->getOption('downloadByOther', $scriptProperties);
/**
 * Turn on the ajax mode for the script.
 * @options: 1 | 0
 * @default: 0
 * @var bool
 * @since ver 2.0.0
 */
$scriptProperties['ajaxMode'] = $modx->getOption('ajaxMode', $scriptProperties);
/**
 * The MODX's resource page id as the Ajax processor file
 * @var int
 * @since ver 2.0.0
 * @subpackage FileDownloadController
 */
$scriptProperties['ajaxControllerPage'] = $modx->getOption('ajaxControllerPage', $scriptProperties);
/**
 * The Ajax's element container id
 * @default: file-download
 * @example <div id="file-download"></div>
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['ajaxContainerId'] = $modx->getOption('ajaxContainerId', $scriptProperties, 'file-download');
/**
 * FileDownloadR's Javascript file for the page header
 * @default: empty
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['fileJs'] = $modx->getOption('fileJs', $scriptProperties);
/**
 * FileDownloadR's Cascading Style Sheet file for the page header
 * @default: assets/components/filedownloadr/css/fd.css
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['fileCss'] = $modx->getOption('fileCss', $scriptProperties
        , $modx->getOption('assets_url') . 'components/filedownloadr/css/fd.css');

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
$scriptProperties['saltText'] = $modx->getOption('saltText', $scriptProperties);
/**
 * This parameter provides the direct link
 * @default: 0
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['directLink'] = $modx->getOption('directLink', $scriptProperties, 0);
/**
 * This is a given ID to the snippet to deal with multiple snippet calls and
 * &browseDirectories altogether
 * @default: null
 * @var string
 */
$scriptProperties['fdlid'] = $modx->getOption('fdlid', $scriptProperties);
/**
 * Attach plugin to the output
 * @default: null
 * @var string
 */
$scriptProperties['plugins'] = $modx->getOption('plugins', $scriptProperties);
/**
 * This is the breadcrumb's link template (chunk/file)
 * @options: chunk's name
 * @default: tpl-breadcrumb
 * @var string
 * @since ver 2.0.0
 */
$scriptProperties['tplBreadcrumb'] = $modx->getOption('tplBreadcrumb', $scriptProperties, 'tpl-breadcrumb');
/**
 * This is the separator character for the breadcrumb
 * @default: /
 * @var string
 */
$scriptProperties['breadcrumbSeparator'] = $modx->getOption('breadcrumbSeparator', $scriptProperties, ' / ');

/**
 * prefix for the placeholders
 * @default: fd.
 * @var string
 */
$scriptProperties['prefix'] = $modx->getOption('prefix', $scriptProperties, 'fd.');

/**
 * Media Source's ID
 * @default: 0
 * @var integer
 */
$scriptProperties['mediaSourceId'] = (int) $modx->getOption('mediaSourceId', $scriptProperties, 0);

/**
 * Use IP location or not
 * @default: false
 * @var boolean
 */
$scriptProperties['useGeolocation'] = (boolean) $modx->getOption('useGeolocation', $scriptProperties, $modx->getOption('filedownloadr.use_geolocation', $scriptProperties, false));

/**
 * API key of IPInfoDB.com
 * @default: ''
 * @var string
 */
$scriptProperties['geoApiKey'] = $modx->getOption('geoApiKey', $scriptProperties, $modx->getOption('filedownloadr.ipinfodb_api_key', $scriptProperties, ''));

/**
 * Show empty folder when required
 * @link https://github.com/goldsky/FileDownload-R/issues/42
 * @default: 0
 * @var integer
 */
$scriptProperties['showEmptyFolder'] = (boolean) $modx->getOption('showEmptyFolder', $scriptProperties, 0);

array_walk($scriptProperties, create_function('&$val', 'if (!is_array($val)) $val = trim($val);'));

// $modx->getService brings more problem on multiple calls
if (!class_exists('FileDownloadR')) {
    require_once $modx->getOption('core_path') . 'components/filedownloadr/model/filedownloadr/filedownloadr.class.php';
}
$fdl = new FileDownloadR($modx, $scriptProperties);

if (!($fdl instanceof FileDownloadR)) {
    return '[FileDownload] instanceof error.';
}

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

/**
 * do sanitizing first
 */
if (!empty($_GET['fdldir']) || !empty($_GET['fdlfile'])) {
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
    $sanitizedGets = $modx->sanitize($_GET);
}

if (empty($scriptProperties['downloadByOther'])) {
    if (!empty($sanitizedGets['fdldir'])) {
        $checkHash = $fdl->checkHash($modx->context->key, $sanitizedGets['fdldir']);
        if (!$checkHash) {
            return;
        }
        if ((!empty($sanitizedGets['fdlid']) && !empty($scriptProperties['fdlid'])) &&
                ($sanitizedGets['fdlid'] != $scriptProperties['fdlid'])) {
            $selected = false;
        } else {
            $selected = true;
        }
        if ($selected) {
            $setDir = $fdl->setDirProp($sanitizedGets['fdldir'], $selected);
            if (!$setDir) {
                return;
            }
        }
    }
    if (!empty($sanitizedGets['fdlfile'])) {
        $checkHash = $fdl->checkHash($modx->context->key, $sanitizedGets['fdlfile']);
        if (!$checkHash) {
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

if (empty($contents) && !$scriptProperties['showEmptyFolder']) {
    return;
}

if (!empty($toArray)) {
    $output = '<pre>' . print_r($contents, true) . '</pre>';
} else {
    $output = $fdl->parseTemplate();
}

if (!empty($toPlaceholder)) {
    $modx->setPlaceholder($toPlaceholder, $output);
    return '';
}
return $output;
