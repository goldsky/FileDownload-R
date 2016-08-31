<?php

$events = array(
    'OnLoad' => array(
        'properties' => array(),
        'returnType' => array(
            NULL
        )
    ),
    'BeforeDirOpen' => array(
        'properties' => array(
            'dirPath'
        ),
        'returnType' => array(
            NULL,
            false,
            'continue'
        )
    ),
    'AfterDirOpen' => array(
        'properties' => array(
            'dirPath',
            'contents'
        ),
        'returnType' => array(
            NULL,
            false,
            'continue'
        )
    ),
    'BeforeFileDownload' => array(
        'properties' => array(
            'hash',
            'ctx',
            'filePath',
            'count'
        ),
        'returnType' => array(
            NULL,
            false
        )
    ),
    'AfterFileDownload' => array(
        'properties' => array(
            'hash',
            'ctx',
            'filePath',
            'count'
        ),
        'returnType' => array(
            NULL
        )
    ),
);
return $events;