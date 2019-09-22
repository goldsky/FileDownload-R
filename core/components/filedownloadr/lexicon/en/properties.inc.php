<?php
/**
 * Default English lexicon topic
 *
 * @language en
 * @package filedownload
 * @subpackage lexicon
 */
/* FileDownload & FileDownloadLink snippet */

$_lang['filedownloadr.filedownload.ajaxContainerId'] = 'The Ajax\'s element container id.';
$_lang['filedownloadr.filedownload.ajaxControllerPage'] = 'The MODX\'s resource page id as the Ajax processor file';
$_lang['filedownloadr.filedownload.browseDirectories'] = 'Allows users to view subdirectories of the specified directory. When using this feature the following templates get used: parent & directory.';
$_lang['filedownloadr.filedownload.chkDesc'] = 'This allows descriptions to be added to the file listing included in a chunk. All of the files and descriptions should be listed in the chunk using the following format: path to file/filename|description||';
$_lang['filedownloadr.filedownload.countDownloads'] = 'With the countDownloads parameter set to 1, everytime a user downloads a file, it will be tracked in a database table.';
$_lang['filedownloadr.filedownload.cssAltRow'] = 'This specifies the class that will be applied to every other file/folder so a ledger look can be styled.';
$_lang['filedownloadr.filedownload.cssDir'] = 'This specifies the class name that will be applied to all directories.';
$_lang['filedownloadr.filedownload.cssExtension'] = 'With this parameter set to 1, a class will be added to each file according to the file\'s extension.';
$_lang['filedownloadr.filedownload.cssExtensionPrefix'] = 'Prefix to the cssExtension class name.';
$_lang['filedownloadr.filedownload.cssExtensionSuffix'] = 'Suffix to the cssExtension class name';
$_lang['filedownloadr.filedownload.cssFile'] = 'This specifies the class name that will be applied to all file rows.';
$_lang['filedownloadr.filedownload.cssFirstDir'] = 'This specifies the class name that will be applied to the first directory.';
$_lang['filedownloadr.filedownload.cssFirstFile'] = 'This specifies the class name that will be applied to the first file.';
$_lang['filedownloadr.filedownload.cssGroupDir'] = 'This specifies the class name that will be applied to the directory for multi-directories grouping.';
$_lang['filedownloadr.filedownload.cssLastDir'] = 'This specifies the class name that will be applied to the last folder.';
$_lang['filedownloadr.filedownload.cssLastFile'] = 'This specifies the class name that will be applied to the last file.';
$_lang['filedownloadr.filedownload.cssPath'] = 'This specifies the class that will be applied to the path when using directory browsing.';
$_lang['filedownloadr.filedownload.dateFormat'] = 'PHP\'s date formatting for each file in the output.';
$_lang['filedownloadr.filedownload.extHidden'] = 'This will exclude the files displayed to files with a valid extension from the comma delimited list of file extensions.';
$_lang['filedownloadr.filedownload.extShown'] = 'This will limit the files displayed to files with a valid extension from the list. comma delimited list of file extensions';
$_lang['filedownloadr.filedownload.fileCss'] = 'FileDownload\'s Cascading Style Sheet file for the page header';
$_lang['filedownloadr.filedownload.fileJs'] = 'FileDownload\'s Javascript file for the page header';
$_lang['filedownloadr.filedownload.getDir'] = 'This is used to specify which directories to display with the snippet. Multiple directories can be specified by seperating them with a comma. When specifying multiple directories the directory browsing functionality is no longer available.';
$_lang['filedownloadr.filedownload.getFile'] = 'This will make the snippet output only the file specified. The getFolder parameter is still required and getFile should be a file inside of the directory. This allows for use of the download script and download counting with a single file.';
$_lang['filedownloadr.filedownload.groupByDirectory'] = 'When multiple directories are specified in the getDir parameter, this parameter will group the files by directory. The directory template will be added above each group.';
$_lang['filedownloadr.filedownload.imgLocat'] = 'Path to the images to associate with each file extension. The images will be outputted with [+fd.image+] placeholder.';
$_lang['filedownloadr.filedownload.imgTypes'] = 'A chunk\'s name to allow associations between file extensions and an image.';
$_lang['filedownloadr.filedownload.noDownload'] = 'This property will make the list only displays files without their download links.';
$_lang['filedownloadr.filedownload.saltText'] = 'Fill this parameter with any text. This text will be added to the file\'s link to disguise the direct paths';
$_lang['filedownloadr.filedownload.sortBy'] = 'This allows the files to be sorted by all of the fields listed. When using the directory browsing feature the files will be sorted by type first, this will put the directories first in the list. When multiple directories are specified and the group by directory feature is used; the files are sorted by path first to keep the files in order by directory.';
$_lang['filedownloadr.filedownload.sortBy.count'] = 'Count';
$_lang['filedownloadr.filedownload.sortBy.date'] = 'Date';
$_lang['filedownloadr.filedownload.sortBy.description'] = 'Description';
$_lang['filedownloadr.filedownload.sortBy.extension'] = 'Extension';
$_lang['filedownloadr.filedownload.sortBy.filename'] = 'Filename';
$_lang['filedownloadr.filedownload.sortBy.fullPath'] = 'Full Path';
$_lang['filedownloadr.filedownload.sortBy.path'] = 'Path';
$_lang['filedownloadr.filedownload.sortBy.size'] = 'Size';
$_lang['filedownloadr.filedownload.sortBy.sizeText'] = 'Size (text)';
$_lang['filedownloadr.filedownload.sortBy.type'] = 'Type';
$_lang['filedownloadr.filedownload.sortByCaseSensitive'] = 'Case sensitive option for sorting.';
$_lang['filedownloadr.filedownload.sortOrder'] = 'Sort order';
$_lang['filedownloadr.filedownload.sortOrder.ascending'] = 'Ascending';
$_lang['filedownloadr.filedownload.sortOrder.descending'] = 'Descending';
$_lang['filedownloadr.filedownload.sortOrderNatural'] = 'Sort order option by a natural order';
$_lang['filedownloadr.filedownload.tpl'] = 'Template with @BINDING (@INLINE / @FILE / @CHUNK) ability';
$_lang['filedownloadr.filedownload.tplDir'] = 'This is the folder template (chunk/file) if it is accessible';
$_lang['filedownloadr.filedownload.tplFile'] = 'This is the file row template (chunk/file)';
$_lang['filedownloadr.filedownload.tplFile'] = 'This is the file row template (chunk/file)';
$_lang['filedownloadr.filedownload.tplGroupDir'] = 'This is the template of the directory path if the &groupByDirectory is enabled';
$_lang['filedownloadr.filedownload.tplIndex'] = 'index.html file/chunk to hide the download folders';
$_lang['filedownloadr.filedownload.tplWrapper'] = 'This is the container template (chunk/file) of all of the snippet\'s results';
$_lang['filedownloadr.filedownload.userGroups'] = 'This will make the download link active for users that belong to the specified groups. If a user is not logged in they will receive a JavaScript alert with the message contained in the noDownload language setting. Multiple groups can be specified by using a comma delimited list.';

