<?php
/**
 * @package filedownloadr
 */
$xpdo_meta_map['fdCount']= array (
  'package' => 'filedownloadr',
  'version' => '1.1',
  'table' => 'fd_count',
  'extends' => 'xPDOSimpleObject',
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
