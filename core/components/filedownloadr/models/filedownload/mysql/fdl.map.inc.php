<?php
$xpdo_meta_map['FDL']= array (
  'package' => 'filedownload',
  'table' => 'fd_count',
  'fields' => 
  array (
    'ctx' => 'web',
    'filename' => '',
    'count' => 0,
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
    'filename' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '255',
      'phptype' => 'string',
      'null' => true,
      'default' => '',
    ),
    'count' => 
    array (
      'dbtype' => 'int',
      'precision' => '10',
      'attributes' => 'unsigned',
      'phptype' => 'integer',
      'null' => true,
      'default' => 0,
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
);
