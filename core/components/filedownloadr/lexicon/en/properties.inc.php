<?php
/**
 * Default English lexicon topic
 *
 * @language en
 * @package filedownload
 * @subpackage lexicon
 */
/* FileDownload & FileDownloadLink snippet */

$_lang['prop_fd.ajaxContainerId_desc'] = "The Ajax's element container id.";
$_lang['prop_fd.ajaxControllerPage_desc'] = "The MODX's resource page id as the
    Ajax processor file";
$_lang['prop_fd.browseDirectories_desc'] = "Allows users to view subdirectories
    of the specified directory. When using this feature the following templates
    get used: parent & directory.";
$_lang['prop_fd.chkDesc_desc'] = "This allows descriptions to be added to the
    file listing included in a chunk. All of the files and descriptions should
    be listed in the chunk using the following format:
    path to file/filename|description||";
$_lang['prop_fd.countDownloads_desc'] = "With the countDownloads parameter set
    to 1, everytime a user downloads a file, it will be tracked in a database
    table.";
$_lang['prop_fd.cssAltRow_desc'] = "This specifies the class that will be
    applied to every other file/folder so a ledger look can be styled.";
$_lang['prop_fd.cssDir_desc'] = "This specifies the class name that will be
    applied to all directories.";
$_lang['prop_fd.cssExtension_desc'] = "With this parameter set to 1, a class
    will be added to each file according to the file's extension.";
$_lang['prop_fd.cssExtensionPrefix_desc'] = "Prefix to the cssExtension class
    name.";
$_lang['prop_fd.cssExtensionSuffix_desc'] = "Suffix to the cssExtension class
    name";
$_lang['prop_fd.cssFile_desc'] = "This specifies the class name that will be
    applied to all file rows.";
$_lang['prop_fd.cssFirstDir_desc'] = "This specifies the class name that will be
    applied to the first directory.";
$_lang['prop_fd.cssFirstFile_desc'] = "This specifies the class name that will
    be applied to the first file.";
$_lang['prop_fd.cssGroupDir_desc'] = "This specifies the class name that will be
    applied to the directory for multi-directories grouping.";
$_lang['prop_fd.cssLastDir_desc'] = "This specifies the class name that will be
    applied to the last folder.";
$_lang['prop_fd.cssLastFile_desc'] = "This specifies the class name that will be
    applied to the last file.";
$_lang['prop_fd.cssPath_desc'] = "This specifies the class that will be applied
    to the path when using directory browsing.";
$_lang['prop_fd.dateFormat_desc'] = "PHP's date formatting for each file in the
    output.";
$_lang['prop_fd.extHidden_desc'] = "This will exclude the files displayed to
    files with a valid extension from the comma delimited list of file
    extensions.";
$_lang['prop_fd.extShown_desc'] = "This will limit the files displayed to files
    with a valid extension from the list. comma delimited list of file
    extensions";
$_lang['prop_fd.fileCss_desc'] = "FileDownload's Cascading Style Sheet file for
    the page header";
$_lang['prop_fd.fileJs_desc'] = "FileDownload's Javascript file for the page
    header";
$_lang['prop_fd.getDir_desc'] = "This is used to specify which directories to
    display with the snippet. Multiple directories can be specified by
    seperating them with a comma. When specifying multiple directories the
    directory browsing functionality is no longer available.";
$_lang['prop_fd.getFile_desc'] = "This will make the snippet output only the
    file specified. The getFolder parameter is still required and getFile should
    be a file inside of the directory. This allows for use of the download
    script and download counting with a single file.";
$_lang['prop_fd.groupByDirectory_desc'] = "When multiple directories are
    specified in the getDir parameter, this parameter will group the files by
    directory. The directory template will be added above each group.";
$_lang['prop_fd.imgLocat_desc'] = "Path to the images to associate with each
    file extension. The images will be outputted with [+fd.image+]
    placeholder.";
$_lang['prop_fd.imgTypes_desc'] = "A chunk's name to allow associations between
    file extensions and an image.";
$_lang['prop_fd.saltText_desc'] = "Fill this parameter with any text. This text
    will be added to the file's link to disguise the direct paths";
$_lang['prop_fd.sortBy_desc'] = "This allows the files to be sorted by all of
    the fields listed. When using the directory browsing feature the files will
    be sorted by type first, this will put the directories first in the list.
    When multiple directories are specified and the group by directory feature
    is used; the files are sorted by path first to keep the files in order by
    directory.";
$_lang['prop_fd.sortByCaseSensitive_desc'] = "Case sensitive option for
    sorting.";
$_lang['prop_fd.sortOrder_desc'] = "Sort order";
$_lang['prop_fd.sortOrderNatural_desc'] = "Sort order option by a natural
    order";
$_lang['prop_fd.tplDir_desc'] = "This is the folder template (chunk/file) if it
    is accessible";
$_lang['prop_fd.tplFile_desc'] = "This is the file row template (chunk/file)";
$_lang['prop_fd.tpl_desc'] = "Template with @BINDING (@CODE / @FILE [/@CHUNK]) ability";
$_lang['prop_fd.tplGroupDir_desc'] = "This is the template of the directory path
    if the &groupByDirectory is enabled";
$_lang['prop_fd.tplIndex_desc'] = "index.html file/chunk to hide the download
    folders";
$_lang['prop_fd.tplWrapper_desc'] = "This is the container template (chunk/file)
    of all of the snippet's results";
$_lang['prop_fd.userGroups_desc'] = "This will make the download link active for
    users that belong to the specified groups. If a user is not logged in they
    will receive a JavaScript alert with the message contained in the noDownload
    language setting. Multiple groups can be specified by using a comma
    delimited list.";