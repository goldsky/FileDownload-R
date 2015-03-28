<?php
$xpdo_meta_map['fdPaths']= array (
  'package' => 'filedownloadr',
  'version' => '1.1',
  'table' => 'paths',
  'extends' => 'xPDOSimpleObject',
  'fields' => 
  array (
    'ctx' => 'web',
    'media_source_id' => 0,
    'filename' => '',
    'hash' => '',
  ),
  'fieldMeta' => 
  array (
    'ctx' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '255',
      'phptype' => 'string',
      'null' => true,
      'default' => 'web',
    ),
    'media_source_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '10',
      'attributes' => 'unsigned',
      'phptype' => 'integer',
      'null' => false,
      'default' => 0,
    ),
    'filename' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '255',
      'phptype' => 'string',
      'null' => true,
      'default' => '',
    ),
    'hash' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '255',
      'phptype' => 'string',
      'null' => true,
      'default' => '',
    ),
  ),
  'composites' => 
  array (
    'Downloads' => 
    array (
      'class' => 'fdDownloads',
      'local' => 'id',
      'foreign' => 'path_id',
      'cardinality' => 'many',
      'owner' => 'local',
    ),
  ),
);
