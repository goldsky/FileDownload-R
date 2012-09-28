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
 * @author      Kyle Jaebker <http://muddydogpaws.com>
 * @author      goldsky <goldsky@fastmail.fm> <http://virtudraft.com>
 * @package     filedownload
 * @subpackage  filedownload snippet
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
$configs['getDir'] = $modx->getOption('getDir', $scriptProperties);
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
$configs['getFile'] = $modx->getOption('getFile', $scriptProperties);

if (empty($configs['getDir']) && empty($configs['getFile'])) {
    return '<!-- FileDownload parameters are empty -->';
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
$configs['browseDirectories'] = $modx->getOption('browseDirectories', $scriptProperties, 0);
// typo fall back
$configs['browseDirectory'] = !empty($scriptProperties['browseDirectory']) ?
        $scriptProperties['browseDirectory'] :
        $configs['browseDirectories'];
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
$configs['groupByDirectory'] = $modx->getOption('groupByDirectory', $scriptProperties, 0);
// typo fall back
$configs['groupByDirectories'] = !empty($scriptProperties['groupByDirectories']) ?
        $scriptProperties['groupByDirectories'] :
        $configs['groupByDirectory'];
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
$configs['sortBy'] = $modx->getOption('sortBy', $scriptProperties, 'filename');
/**
 * Case sensitive option for sorting
 * @options: 1 | 0
 * @default: 0
 * @var bool
 * @since ver 2.0.0
 */
$configs['sortByCaseSensitive'] = $modx->getOption('sortByCaseSensitive', $scriptProperties);
/**
 * Sort files in ascending or descending order.
 * @options: asc | desc
 * @default: asc
 * @var string
 * @since ver 1.2.0
 */
$configs['sortOrder'] = $modx->getOption('sortOrder', $scriptProperties);
/**
 * Sort order option by a natural order
 * @options: 1 | 0
 * @default: 1
 * @var bool
 * @since ver 2.0.0
 */
$configs['sortOrderNatural'] = $modx->getOption('sortOrderNatural', $scriptProperties);
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
$configs['showExt'] = $modx->getOption('showExt', $scriptProperties);
if (!empty($configs['showExt']) && empty($scriptProperties['extShown'])) {
    $configs['extShown'] = $configs['showExt'];
} else {
    $configs['extShown'] = $modx->getOption('extShown', $scriptProperties);
}
unset($configs['showExt']);
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
$configs['hideExt'] = $modx->getOption('hideExt', $scriptProperties);
if (!empty($configs['hideExt']) && empty($scriptProperties['extHidden'])) {
    $configs['extHidden'] = $configs['hideExt'];
} else {
    $configs['extHidden'] = $modx->getOption('extHidden', $scriptProperties);
}
unset($configs['hideExt']);
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
$configs['imgTypes'] = $modx->getOption('imgTypes', $scriptProperties);

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
$configs['tplDir'] = $modx->getOption('tplDir', $scriptProperties, 'tpl-row-dir');
/**
 * This is the file row template (chunk/file)
 * @options: chunk's name
 * @default: tpl-file
 * @var string
 * @since ver 2.0.0
 */
$configs['tplFile'] = $modx->getOption('tplFile', $scriptProperties, 'tpl-row-file');
/**
 * This is the file row template (chunk/file)
 * @options: chunk's name
 * @default: tpl-file
 * @var string
 * @since ver 2.0.0
 */
$configs['tplGroupDir'] = $modx->getOption('tplGroupDir', $scriptProperties, 'tpl-group-dir');
/**
 * This is the container template (chunk/file) of all of the snippet's results
 * @options: chunk's name
 * @default: tpl-wrapper
 * @var string
 * @since ver 2.0.0
 */
$configs['tplWrapper'] = $modx->getOption('tplWrapper', $scriptProperties, 'tpl-wrapper');
/**
 * This is the container template for folders
 * @options: chunk's name, or empty to disable
 * @default: tpl-wrapper-dir
 * @var string
 * @since ver 2.0.0
 */
$configs['tplWrapperDir'] = $modx->getOption('tplWrapperDir', $scriptProperties);
/**
 * This is the container template for files
 * @options: chunk's name, or empty to disable
 * @default: tpl-wrapper-dir
 * @var string
 * @since ver 2.0.0
 */
$configs['tplWrapperFile'] = $modx->getOption('tplWrapperFile', $scriptProperties);
/**
 * index.html file/chunk to hide the download folders
 * @options: chunk's name
 * @default: tpl-index
 * @var string
 * @since ver 2.0.0
 */
$configs['tplIndex'] = $modx->getOption('tplIndex', $scriptProperties, 'tpl-index');

/**
 * Template for forbidden access
 * @options: @BINDINGs
 * @default: @FILE: [[++core_path]]components/filedownload/elements/chunks/tpl-notallowed.chunk.tpl
 * @var string
 * @since ver 2.0.0
 */
$configs['tplNotAllowed'] = $modx->getOption('tplNotAllowed', $scriptProperties, '@FILE: [[++core_path]]components/filedownload/elements/chunks/tpl-notallowed.chunk.tpl');

/**
 * This specifies the class that will be applied to every other file/directory so
 * a ledger look can be styled.
 * @options: css class name
 * @default: fd-alt
 * @var string
 * @since ver 1.2.0
 * @deprecated deprecated since 2.0.0. Use 'cssAltRow' instead.
 */
$configs['altCss'] = $modx->getOption('altCss', $scriptProperties);
if (!empty($configs['altCss']) && empty($scriptProperties['cssAltRow'])) {
    $configs['cssAltRow'] = $configs['altCss'];
} else {
    $configs['cssAltRow'] = $modx->getOption('cssAltRow', $scriptProperties);
}
unset($configs['altCss']);

/**
 * This specifies the class that will be applied to the first directory.
 * @options: css class name
 * @default: fd-firstDir
 * @var string
 * @since ver 1.2.0
 * @deprecated deprecated since 2.0.0. Use 'cssFirstDir' instead.
 */
$configs['firstFolderCss'] = $modx->getOption('firstFolderCss', $scriptProperties);
if (!empty($configs['firstFolderCss']) && empty($scriptProperties['cssFirstDir'])) {
    $configs['cssFirstDir'] = $configs['firstFolderCss'];
} else {
    $configs['cssFirstDir'] = $modx->getOption('cssFirstDir', $scriptProperties);
}
unset($configs['firstFolderCss']);
/**
 * This specified the class that will be applied to the last directory.
 * @options: css class name
 * @default: fd-lastDir
 * @var string
 * @since ver 1.2.0
 * @deprecated deprecated since 2.0.0. Use 'cssLastDir' instead.
 */
$configs['lastFolderCss'] = $modx->getOption('lastFolderCss', $scriptProperties);
if (!empty($configs['lastFolderCss']) && empty($scriptProperties['cssLastDir'])) {
    $configs['cssLastDir'] = $configs['lastFolderCss'];
} else {
    $configs['cssLastDir'] = $modx->getOption('cssLastDir', $scriptProperties);
}
unset($configs['lastFolderCss']);
/**
 * This specified the class that will be applied to the first file.
 * @options: css class name
 * @default: fd-firstFile
 * @var string
 * @since ver 1.2.0
 * @deprecated deprecated since 2.0.0. Use 'cssFirstFile' instead.
 */
$configs['firstFileCss'] = $modx->getOption('firstFileCss', $scriptProperties);
if (!empty($configs['firstFileCss']) && empty($scriptProperties['cssFirstFile'])) {
    $configs['cssFirstFile'] = $configs['firstFileCss'];
} else {
    $configs['cssFirstFile'] = $modx->getOption('cssFirstFile', $scriptProperties);
}
unset($configs['firstFileCss']);
/**
 * This specifies the class that will be applied to the last file.
 * @options: css class name
 * @default: fd-lastFile
 * @var string
 * @since ver 1.2.0
 * @deprecated deprecated since 2.0.0. Use 'cssLastFile' instead.
 */
$configs['lastFileCss'] = $modx->getOption('lastFileCss', $scriptProperties);
if (!empty($configs['lastFileCss']) && empty($scriptProperties['cssLastFile'])) {
    $configs['cssLastFile'] = $configs['lastFileCss'];
} else {
    $configs['cssLastFile'] = $modx->getOption('cssLastFile', $scriptProperties);
}
unset($configs['lastFileCss']);
/**
 * This specifies the class that will be applied to all folders.
 * @options: css class name
 * @default: fd-dir
 * @var string
 * @since ver 1.2.0
 * @deprecated deprecated since 2.0.0. Use 'cssDir' instead.
 */
$configs['folderCss'] = $modx->getOption('folderCss', $scriptProperties);
if (!empty($configs['folderCss']) && empty($scriptProperties['cssDir'])) {
    $configs['cssDir'] = $configs['folderCss'];
} else {
    $configs['cssDir'] = $modx->getOption('cssDir', $scriptProperties);
}
unset($configs['folderCss']);
/**
 * Class name for all files
 * @options: css class name
 * @default: fd-file
 * @var string
 * @since ver 1.2.0
 * @deprecated deprecated since 2.0.0. Use 'cssFile' instead.
 */
$configs['cssFile'] = $modx->getOption('cssFile', $scriptProperties);
/**
 * This specifies the class that will be applied to the directory for multi-
 * directory grouping.
 * @options: css class name
 * @default: fd-group-dir
 * @var string
 * @since ver 1.2.0
 * @deprecated deprecated since 2.0.0. Use 'cssGroupDir' instead.
 */
$configs['directoryCss'] = $modx->getOption('directoryCss', $scriptProperties);
if (!empty($configs['directoryCss']) && empty($scriptProperties['cssGroupDir'])) {
    $configs['cssGroupDir'] = $configs['directoryCss'];
} else {
    $configs['cssGroupDir'] = $modx->getOption('cssGroupDir', $scriptProperties);
}
unset($configs['directoryCss']);
/**
 * This specifies the class that will be applied to the path when using
 * directory browsing.
 * @options: css class name
 * @default: fd-path
 * @var string
 * @since ver 1.2.0
 * @deprecated deprecated since 2.0.0. Use 'cssPath' instead.
 */
$configs['pathCss'] = $modx->getOption('pathCss', $scriptProperties);
if (!empty($configs['pathCss']) && empty($scriptProperties['cssPath'])) {
    $configs['cssPath'] = $configs['pathCss'];
} else {
    $configs['cssPath'] = $modx->getOption('cssPath', $scriptProperties);
}
unset($configs['pathCss']);
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
$configs['extCss'] = $modx->getOption('extCss', $scriptProperties);
if (!empty($configs['extCss']) && empty($scriptProperties['cssExtension'])) {
    $configs['cssExtension'] = $configs['extCss'];
} else {
    $configs['cssExtension'] = $modx->getOption('cssExtension', $scriptProperties);
}
unset($configs['extCss']);
/**
 * Prefix to the above cssExtension class name
 * @default: fd-
 * @example: a pdf would get the class: fd-pdf.
 * @var string
 * @since ver 2.0.0
 */
$configs['cssExtensionPrefix'] = $modx->getOption('cssExtensionPrefix', $scriptProperties, 'fd-');
/**
 * Suffix to the above cssExtension class name
 * @default: null
 * @example: a pdf would get the class: pdfsuffix.
 * @var string
 * @since ver 2.0.0
 */
$configs['cssExtensionSuffix'] = $modx->getOption('cssExtensionSuffix', $scriptProperties);
/**
 * This property will make the list only displays files without their download links.
 * @default: null
 * @var string
 * @since ver 1.2.0
 */
$configs['noDownload'] = $modx->getOption('noDownload', $scriptProperties);
/**
 * Pass the downloading job to the plugin. This provides flexibility to do
 * conditional statements inside the plugins, or initiate the downloading using
 * AJAX
 * @options: 1 | 0
 * @default: 0
 * @var bool
 * @since ver 2.0.0
 */
$configs['downloadByOther'] = $modx->getOption('downloadByOther', $scriptProperties);
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
$configs['fdlid'] = $modx->getOption('fdlid', $scriptProperties);
/**
 * This is a given ID to the snippet to deal with multiple snippet calls and
 * &browseDirectories altogether
 * @default: null
 * @var string
 */
$configs['plugins'] = $modx->getOption('plugins', $scriptProperties);
/**
 * This is the breadcrumb's link template (chunk/file)
 * @options: chunk's name
 * @default: tpl-breadcrumb
 * @var string
 * @since ver 2.0.0
 */
$configs['tplBreadcrumb'] = $modx->getOption('tplBreadcrumb', $scriptProperties, 'tpl-breadcrumb');
/**
 * This is a given ID to the snippet to deal with multiple snippet calls and
 * &browseDirectories altogether
 * @default: null
 * @var string
 */
$configs['breadcrumbSeparator'] = $modx->getOption('breadcrumbSeparator', $scriptProperties, ' / ');

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
    $page = $modx->makeUrl($modx->resource->get('id'), '', '', 'full');
    /**
     * check referrer and the page
     */
    if ($baseRef !== $page) {
        return $modx->sendUnauthorizedPage();
    }
    $sanitizedGets = $modx->sanitize($_GET);
}

if (empty($configs['downloadByOther'])) {
    if (!empty($sanitizedGets['fdldir'])) {
        $checkHash = $fdl->checkHash($modx->context->key, $sanitizedGets['fdldir']);
        if (!$checkHash) {
            return;
        }
        if ((!empty($sanitizedGets['fdlid']) && !empty($configs['fdlid'])) &&
                ($sanitizedGets['fdlid'] != $configs['fdlid'])) {
            $selected = FALSE;
        } else {
            $selected = TRUE;
        }
        if ($selected) {
            $setDir = $fdl->setDirProp($sanitizedGets['fdldir'], $selected);
            if (!$setDir) {
                return;
            }
        }
    } elseif (!empty($sanitizedGets['fdlfile'])) {
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
if (empty($contents)) {
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