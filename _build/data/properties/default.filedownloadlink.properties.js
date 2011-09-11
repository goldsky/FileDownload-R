[{
    "name":"chkDesc",
    "desc":"prop_fd.chkDesc_desc",
    "xtype":"textfield",
    "options":"",
    "value":"",
    "lexicon":"filedownload:properties",
    "overridden":false,
    "desc_trans":"This allows descriptions to be added to the\n    file listing included in a chunk. All of the files and descriptions should\n    be listed in the chunk using the following format:\n    path to file/filename|description||",
    "menu":null
},{
    "name":"countDownloads",
    "desc":"prop_fd.countDownloads_desc",
    "xtype":"combo-boolean",
    "options":"",
    "value":true,
    "lexicon":"filedownload:properties",
    "overridden":false,
    "desc_trans":"With the countDownloads parameter set\n    to 1, everytime a user downloads a file, it will be tracked in a database\n    table.",
    "menu":null
},{
    "name":"dateFormat",
    "desc":"prop_fd.dateFormat_desc",
    "xtype":"textfield",
    "options":"",
    "value":"Y-m-d",
    "lexicon":"filedownload:properties",
    "overridden":false,
    "desc_trans":"PHP's date formatting for each file in the\n    output.",
    "menu":null
},{
    "name":"fileCss",
    "desc":"prop_fd.fileCss_desc",
    "xtype":"textfield",
    "options":"",
    "value":"{assets_url}components/filedownload/css/fd.css",
    "lexicon":"filedownload:properties",
    "overridden":false,
    "desc_trans":"FileDownload's Cascading Style Sheet file for\n    the page header",
    "menu":null
},{
    "name":"fileJs",
    "desc":"prop_fd.fileJs_desc",
    "xtype":"textfield",
    "options":"",
    "value":"{assets_url}components/filedownload/js/fd.js",
    "lexicon":"filedownload:properties",
    "overridden":false,
    "desc_trans":"FileDownload's Javascript file for the page\n    header",
    "menu":null
},{
    "name":"getFile",
    "desc":"prop_fd.getFile_desc",
    "xtype":"textfield",
    "options":"",
    "value":"",
    "lexicon":"filedownload:properties",
    "overridden":false,
    "desc_trans":"This will make the snippet output only the\n    file specified. The getFolder parameter is still required and getFile should\n    be a file inside of the directory. This allows for use of the download\n    script and download counting with a single file.",
    "menu":null
},{
    "name":"imgLocat",
    "desc":"prop_fd.imgLocat_desc",
    "xtype":"textfield",
    "options":"",
    "value":"{assets_url}components/filedownload/img/filetype/",
    "lexicon":"filedownload:properties",
    "overridden":false,
    "desc_trans":"Path to the images to associate with each\n    file extension. The images will be outputted with [+fd.image+]\n    placeholder.",
    "menu":null
},{
    "name":"imgTypes",
    "desc":"prop_fd.imgTypes_desc",
    "xtype":"textfield",
    "options":"",
    "value":"fdImages",
    "lexicon":"filedownload:properties",
    "overridden":false,
    "desc_trans":"A chunk's name to allow associations between\n    file extensions and an image.",
    "menu":null
},{
    "name":"saltText",
    "desc":"prop_fd.saltText_desc",
    "xtype":"textfield",
    "options":"",
    "value":"FileDownload",
    "lexicon":"filedownload:properties",
    "overridden":false,
    "desc_trans":"Fill this parameter with any text. This text\n    will be added to the file's link to disguise the direct paths",
    "menu":null
},{
    "name":"tplCode",
    "desc":"prop_fd.tplCode_desc",
    "xtype":"textfield",
    "options":"",
    "value":"<a href=\"[[+link]]\">[[+filename]]</a>",
    "lexicon":"filedownload:properties",
    "overridden":false,
    "desc_trans":"prop_fd.tplCode_desc",
    "menu":null
},{
    "name":"userGroups",
    "desc":"prop_fd.userGroups_desc",
    "xtype":"textfield",
    "options":"",
    "value":"",
    "lexicon":"filedownload:properties",
    "overridden":false,
    "desc_trans":"This will make the download link active for\n    users that belong to the specified groups. If a user is not logged in they\n    will receive a JavaScript alert with the message contained in the noDownload\n    language setting. Multiple groups can be specified by using a comma\n    delimited list.",
    "menu":null
}]