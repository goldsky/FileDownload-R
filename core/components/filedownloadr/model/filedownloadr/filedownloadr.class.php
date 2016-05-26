<?php

/**
 * FileDownload
 *
 * Copyright 2011-2016 by goldsky <goldsky@virtudraft.com>
 *
 * This file is part of FileDownload, a file downloader for MODX Revolution
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
 * The main class for the FileDownload snippet for MODX Revolution
 * This is the conversion of the original FileDownload snippet for MODX
 * Evolution, which was originally created by Kyle Jaebker.
 * The main parameters are taken from that version so any conversion can be done
 * smoothly.
 *
 * @author goldsky <goldsky@virtudraft.com>
 * @package main class
 */
class FileDownloadR {

    const VERSION = '2.0.0';
    const RELEASE = 'pl';

    /**
     * modX object
     * @var object
     */
    public $modx;

    /**
     * $scriptProperties
     * @var array
     */
    public $config = array();

    /**
     * To hold error message
     * @var array
     */
    private $_error = array();

    /**
     * To hold output message
     * @var array
     */
    private $_output = array();

    /**
     * To hold placeholder array, flatten array with prefixable
     * @var array
     */
    private $_placeholders = array();

    /**
     * To hold plugin
     * @var array
     */
    public $plugins;

    /**
     * To hold counting
     * @var array
     */
    private $_count = array();

    /**
     * To hold image type
     * @var array
     */
    private $_imgType = array();

    /**
     * store the chunk's HTML to property to save memory of loop rendering
     * @var array
     */
    private $_chunks = array();
    public $mediaSource;

    /**
     * Directory Separator
     * @var string
     */
    public $ds;

    /**
     * constructor
     * @param   modX    $modx
     * @param   array   $config    parameters
     */
    public function __construct(modX $modx, $config = array()) {
        $this->modx = &$modx;

        $corePath = $this->modx->getOption('core_path');
        $basePath = $corePath . 'components/filedownloadr/';
        $assetsUrl = $this->modx->getOption('assets_url') . 'components/filedownloadr/';

        $this->_output = array(
            'rows' => '',
            'dirRows' => '',
            'fileRows' => ''
        );
        $this->config = array_merge(array(
            'version' => self::VERSION . '-' . self::RELEASE,
            'corePath' => $corePath,
            'basePath' => $basePath,
            'modelPath' => $basePath . 'model/',
            'processorsPath' => $basePath . 'processors/',
            'controllersPath' => $basePath . 'controllers/',
            'chunksPath' => $basePath . 'elements/chunks/',
            'jsUrl' => $assetsUrl . 'js/',
            'cssUrl' => $assetsUrl . 'css/',
            'imgTypeUrl' => $assetsUrl . 'img/filetypes/',
            'imgLocat' => $assetsUrl . 'img/filetypes/',
            'assetsUrl' => $assetsUrl,
            'encoding' => 'utf-8',
            'imgTypes' => 'fdimages'
                ), $config);

        $tablePrefix = $this->modx->getOption('filedownloadr.table_prefix', null, $this->modx->config[modX::OPT_TABLE_PREFIX] . 'fd_');
        $this->modx->addPackage('filedownloadr', $this->config['modelPath'], $tablePrefix);

        if (!$this->modx->lexicon) {
            $this->modx->getService('lexicon', 'modLexicon');
        }
        $this->modx->lexicon->load('filedownloadr:default');

        $this->_imgType = $this->_imgTypeProp();
        if (empty($this->_imgType)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[FileDownloadR] could not load image types.', '', __METHOD__, __FILE__, __LINE__);
            return false;
        }
        if (!empty($this->config['encoding'])) {
            mb_internal_encoding($this->config['encoding']);
        }

        if (!empty($this->config['plugins'])) {
            if (!$this->modx->loadClass('filedownloadr.FileDownloadPlugin', $this->config['modelPath'], true, true)) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, '[FileDownloadR] could not load plugin class.', '', __METHOD__, __FILE__, __LINE__);
                return false;
            }
            $this->plugins = new FileDownloadPlugin($this);
        }

        if (!empty($this->config['mediaSourceId'])) {
            $this->mediaSource = $this->modx->getObject('sources.modMediaSource', array('id' => $this->config['mediaSourceId']));
            if ($this->mediaSource) {
                $this->mediaSource->initialize();
            }
        }

        if (empty($this->mediaSource)) {
            $this->ds = DIRECTORY_SEPARATOR;
        } else {
            $this->ds = '/';
        }

        $this->config['getDir'] = !empty($this->config['getDir']) ? $this->_checkPath($this->config['getDir']) : '';
        $this->config['origDir'] = !empty($this->config['origDir']) ? $this->trimArray(@explode(',', $this->config['origDir'])) : '';
        $this->config['getFile'] = !empty($this->config['getFile']) ? $this->_checkPath($this->config['getFile']) : '';
        $this->config = $this->replacePropPhs($this->config);
    }

    /**
     * Set class configuration exclusively for multiple snippet calls
     * @param   array   $config     snippet's parameters
     */
    public function setConfigs($config = array()) {
        // Clear previous output for subsequent snippet calls
        $this->_output = array(
            'rows' => '',
            'dirRows' => '',
            'fileRows' => ''
        );
        $config['getDir'] = !empty($config['getDir']) ? $this->_checkPath($config['getDir']) : '';
        $config['origDir'] = !empty($config['origDir']) ? $this->trimArray(@explode(',', $this->config['origDir'])) : '';
        $config['getFile'] = !empty($config['getFile']) ? $this->_checkPath($config['getFile']) : '';

        $config = $this->replacePropPhs($config);

        $this->config = array_merge($this->config, $config);
    }

    /**
     * Define individual config for the class
     * @param   string  $key    array's key
     * @param   string  $val    array's value
     */
    public function setConfig($key, $val) {
        $this->config[$key] = $val;
    }

    public function getConfig($key) {
        return $this->config[$key];
    }

    public function getConfigs() {
        return $this->config;
    }

    /**
     * Set string error for boolean returned methods
     * @return  void
     */
    public function setError($msg) {
        $this->_error[] = $msg;
    }

    /**
     * Get string error for boolean returned methods
     * @param   string  $delimiter  delimiter of the imploded output (default: "\n")
     * @return  string  output
     */
    public function getError($delimiter = "\n") {
        if ($delimiter === '\n') {
            $delimiter = "\n";
        }
        return @implode($delimiter, $this->_error);
    }

    /**
     * Set string output for boolean returned methods
     * @return  void
     */
    public function setOutput($msg) {
        $this->_output[] = $msg;
    }

    /**
     * Get string output for boolean returned methods
     * @param   string  $delimiter  delimiter of the imploded output (default: "\n")
     * @return  string  output
     */
    public function getOutput($delimiter = "\n") {
        if ($delimiter === '\n') {
            $delimiter = "\n";
        }
        return @implode($delimiter, $this->_output);
    }

    /**
     * Set internal placeholder
     * @param   string  $key    key
     * @param   string  $value  value
     * @param   string  $prefix add prefix if it's required
     */
    public function setPlaceholder($key, $value, $prefix = '') {
        $prefix = !empty($prefix) ? $prefix : (isset($this->config['phsPrefix']) ? $this->config['phsPrefix'] : '');
        $this->_placeholders[$prefix . $key] = $this->trimString($value);
    }

    /**
     * Set internal placeholders
     * @param   array   $placeholders   placeholders in an associative array
     * @param   string  $prefix         add prefix if it's required
     * @param   boolean $merge          define whether the output will be merge to global properties or not
     * @param   string  $delimiter      define placeholder's delimiter
     * @return  mixed   boolean|array of placeholders
     */
    public function setPlaceholders($placeholders, $prefix = '', $merge = true, $delimiter = '.') {
        if (empty($placeholders)) {
            return false;
        }
        $prefix = !empty($prefix) ? $prefix : (isset($this->config['phsPrefix']) ? $this->config['phsPrefix'] : '');
        $placeholders = $this->trimArray($placeholders);
        $placeholders = $this->implodePhs($placeholders, rtrim($prefix, $delimiter));
        // enclosed private scope
        if ($merge) {
            $this->_placeholders = array_merge($this->_placeholders, $placeholders);
        }
        // return only for this scope
        return $placeholders;
    }

    /**
     * Get internal placeholders in an associative array
     * @return array
     */
    public function getPlaceholders() {
        return $this->_placeholders;
    }

    /**
     * Get an internal placeholder
     * @param   string  $key    key
     * @return  string  value
     */
    public function getPlaceholder($key) {
        return $this->_placeholders[$key];
    }

    /**
     * Merge multi dimensional associative arrays with separator
     * @param   array   $array      raw associative array
     * @param   string  $keyName    parent key of this array
     * @param   string  $separator  separator between the merged keys
     * @param   array   $holder     to hold temporary array results
     * @return  array   one level array
     */
    public function implodePhs(array $array, $keyName = null, $separator = '.', array $holder = array()) {
        $phs = !empty($holder) ? $holder : array();
        foreach ($array as $k => $v) {
            $key = !empty($keyName) ? $keyName . $separator . $k : $k;
            if (is_array($v)) {
                $phs = $this->implodePhs($v, $key, $separator, $phs);
            } else {
                $phs[$key] = $v;
            }
        }
        return $phs;
    }

    /**
     * Trim string value
     * @param   string  $string     source text
     * @param   string  $charlist   defined characters to be trimmed
     * @link http://php.net/manual/en/function.trim.php
     * @return  string  trimmed text
     */
    public function trimString($string, $charlist = null) {
        if (empty($string) && !is_numeric($string)) {
            return '';
        }
        $string = htmlentities($string);
        // blame TinyMCE!
        $string = preg_replace('/(&Acirc;|&nbsp;)+/i', '', $string);
        $string = trim($string, $charlist);
        $string = trim(preg_replace('/\s+^(\r|\n|\r\n)/', ' ', $string));
        $string = html_entity_decode($string);
        return $string;
    }

    /**
     * Trim array values
     * @param   array   $array          array contents
     * @param   string  $charlist       [default: null] defined characters to be trimmed
     * @link http://php.net/manual/en/function.trim.php
     * @return  array   trimmed array
     */
    public function trimArray($input, $charlist = null) {
        if (is_array($input)) {
            $output = array_map(array($this, 'trimArray'), $input);
        } else {
            $output = $this->trimString($input, $charlist);
        }

        return $output;
    }

    /**
     * Parsing template
     * @param   string  $tpl    @BINDINGs options
     * @param   array   $phs    placeholders
     * @return  string  parsed output
     * @link    http://forums.modx.com/thread/74071/help-with-getchunk-and-modx-speed-please?page=2#dis-post-413789
     */
    public function parseTpl($tpl, array $phs = array()) {
        $output = '';

        if (isset($this->_chunks[$tpl]) && !empty($this->_chunks[$tpl])) {
            return $this->parseTplCode($this->_chunks[$tpl], $phs);
        }

        if (preg_match('/^(@CODE|@INLINE)/i', $tpl)) {
            $tplString = preg_replace('/^(@CODE|@INLINE)/i', '', $tpl);
            // tricks @CODE: / @INLINE:
            $tplString = ltrim($tplString, ':');
            $tplString = trim($tplString);
            $this->_chunks[$tpl] = $tplString;
            $output = $this->parseTplCode($tplString, $phs);
        } elseif (preg_match('/^@FILE/i', $tpl)) {
            $tplFile = preg_replace('/^@FILE/i', '', $tpl);
            // tricks @FILE:
            $tplFile = ltrim($tplFile, ':');
            $tplFile = trim($tplFile);
            $tplFile = $this->replacePropPhs($tplFile);
            try {
                $output = $this->parseTplFile($tplFile, $phs);
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }
        // ignore @CHUNK / @CHUNK: / empty @BINDING
        else {
            $tplChunk = preg_replace('/^@CHUNK/i', '', $tpl);
            // tricks @CHUNK:
            $tplChunk = ltrim($tpl, ':');
            $tplChunk = trim($tpl);

            $chunk = $this->modx->getObject('modChunk', array('name' => $tplChunk), true);
            if (empty($chunk)) {
                // try to use @splittingred's fallback
                $f = $this->config['chunksPath'] . strtolower($tplChunk) . '.chunk.tpl';
                try {
                    $output = $this->parseTplFile($f, $phs);
                } catch (Exception $e) {
                    $output = $e->getMessage();
                    return 'Chunk: ' . $tplChunk . ' is not found, neither the file ' . $output;
                }
            } else {
//                $output = $this->modx->getChunk($tplChunk, $phs);
                /**
                 * @link    http://forums.modx.com/thread/74071/help-with-getchunk-and-modx-speed-please?page=4#dis-post-464137
                 */
                $chunk = $this->modx->getParser()->getElement('modChunk', $tplChunk);
                $this->_chunks[$tpl] = $chunk->get('content');
                $chunk->setCacheable(false);
                $chunk->_processed = false;
                $output = $chunk->process($phs);
            }
        }

        return $output;
    }

    /**
     * Parsing inline template code
     * @param   string  $code   HTML with tags
     * @param   array   $phs    placeholders
     * @return  string  parsed output
     */
    public function parseTplCode($code, array $phs = array()) {
        $chunk = $this->modx->newObject('modChunk');
        $chunk->setContent($code);
        $chunk->setCacheable(false);
        $phs = $this->replacePropPhs($phs);
        $chunk->_processed = false;
        return $chunk->process($phs);
    }

    /**
     * Parsing file based template
     * @param   string  $file   file path
     * @param   array   $phs    placeholders
     * @return  string  parsed output
     * @throws  Exception if file is not found
     */
    public function parseTplFile($file, array $phs = array()) {
        if (!file_exists($file)) {
            throw new Exception('File: ' . $file . ' is not found.');
        }
        $o = file_get_contents($file);
        $this->_chunks[$file] = $o;
        $chunk = $this->modx->newObject('modChunk');

        // just to create a name for the modChunk object.
        $name = strtolower($this->_basename($file));
        $name = rtrim($name, '.tpl');
        $name = rtrim($name, '.chunk');
        $chunk->set('name', $name);

        $chunk->setCacheable(false);
        $chunk->setContent($o);
        $chunk->_processed = false;
        $output = $chunk->process($phs);

        return $output;
    }

    /**
     * If the chunk is called by AJAX processor, it needs to be parsed for the
     * other elements to work, like snippet and output filters.
     *
     * Example:
     * <pre><code>
     * <?php
     * $content = $myObject->parseTpl('tplName', $placeholders);
     * $content = $myObject->processElementTags($content);
     * </code></pre>
     *
     * @param   string  $content    the chunk output
     * @param   array   $options    option for iteration
     * @return  string  parsed content
     */
    public function processElementTags($content, array $options = array()) {
        $maxIterations = intval($this->modx->getOption('parser_max_iterations', $options, 10));
        if (!$this->modx->parser) {
            $this->modx->getParser();
        }
        $this->modx->parser->processElementTags('', $content, true, false, '[[', ']]', array(), $maxIterations);
        $this->modx->parser->processElementTags('', $content, true, true, '[[', ']]', array(), $maxIterations);
        return $content;
    }

    /**
     * Replace the property's placeholders
     * @param   string|array    $subject    Property
     * @return  array           The replaced results
     */
    public function replacePropPhs($subject) {
        $pattern = array(
            '/\{core_path\}/',
            '/\{base_path\}/',
            '/\{assets_url\}/',
            '/\{filemanager_path\}/',
            '/\[\[\+\+core_path\]\]/',
            '/\[\[\+\+base_path\]\]/'
        );
        $replacement = array(
            $this->modx->getOption('core_path'),
            $this->modx->getOption('base_path'),
            $this->modx->getOption('assets_url'),
            $this->modx->getOption('filemanager_path'),
            $this->modx->getOption('core_path'),
            $this->modx->getOption('base_path')
        );
        if (is_array($subject)) {
            $parsedString = array();
            foreach ($subject as $k => $s) {
                if (is_array($s)) {
                    $s = $this->replacePropPhs($s);
                }
                $parsedString[$k] = preg_replace($pattern, $replacement, $s);
            }
            return $parsedString;
        } else {
            return preg_replace($pattern, $replacement, $subject);
        }
    }

    /**
     * Get the clean path array and clean up some duplicate slashes
     * @param   string  $paths  multiple paths with comma separated
     * @return  array   Dir paths in an array
     */
    private function _checkPath($paths) {
        $forbiddenFolders = array(
            realpath(MODX_CORE_PATH),
            realpath(MODX_PROCESSORS_PATH),
            realpath(MODX_CONNECTORS_PATH),
            realpath(MODX_MANAGER_PATH),
            realpath(MODX_BASE_PATH)
        );
        $cleanPaths = array();
        if (!empty($paths)) {
            $xPath = @explode(',', $paths);
            foreach ($xPath as $path) {
                if (empty($path)) {
                    continue;
                }
                $path = $this->trimString($path);
                if (empty($this->mediaSource)) {
                    $fullPath = realpath($path);
                    if (empty($fullPath)) {
                        $fullPath = realpath(MODX_BASE_PATH . $path);
                        if (empty($fullPath)) {
                            continue;
                        }
                    }
                } else {
                    $fullPath = $path;
                }
                if (in_array($fullPath, $forbiddenFolders)) {
                    continue;
                }
                $cleanPaths[$path] = $fullPath;
            }
        }

        return $cleanPaths;
    }

    /**
     * View any string as a hexdump.
     *
     * This is most commonly used to view binary data from streams
     * or sockets while debugging, but can be used to view any string
     * with non-viewable characters.
     *
     * @version     1.3.2
     * @author      Aidan Lister <aidan@php.net>
     * @author      Peter Waller <iridum@php.net>
     * @link        http://aidanlister.com/2004/04/viewing-binary-data-as-a-hexdump-in-php/
     * @param       string  $data        The string to be dumped
     * @param       bool    $htmloutput  [default: true] Set to false for non-HTML output
     * @param       bool    $uppercase   [default: false] Set to true for uppercase hex
     * @param       bool    $return      [default: false] Set to true to return the dump
     */
    public function hexdump($data, $htmloutput = true, $uppercase = false, $return = false) {
        // Init
        $hexi = '';
        $ascii = '';
        $dump = ($htmloutput === true) ? '<pre>' : '';
        $offset = 0;
        $len = strlen($data);

        // Upper or lower case hexadecimal
        $x = ($uppercase === false) ? 'x' : 'X';

        // Iterate string
        for ($i = $j = 0; $i < $len; $i++) {
            // Convert to hexidecimal
            $hexi .= sprintf("%02$x ", ord($data[$i]));

            // Replace non-viewable bytes with '.'
            if (ord($data[$i]) >= 32) {
                $ascii .= ($htmloutput === true) ?
                        htmlentities($data[$i]) :
                        $data[$i];
            } else {
                $ascii .= '.';
            }

            // Add extra column spacing
            if ($j === 7) {
                $hexi .= ' ';
                $ascii .= ' ';
            }

            // Add row
            if (++$j === 16 || $i === $len - 1) {
                // Join the hexi / ascii output
                $dump .= sprintf("%04$x  %-49s  %s", $offset, $hexi, $ascii);

                // Reset vars
                $hexi = $ascii = '';
                $offset += 16;
                $j = 0;

                // Add newline
                if ($i !== $len - 1) {
                    $dump .= "\n";
                }
            }
        }

        // Finish dump
        $dump .= $htmloutput === true ?
                '</pre>' :
                '';
        $dump .= "\n";

        // Output method
        if ($return === false) {
            echo $dump;
        } else {
            return $dump;
        }
    }

    /**
     * Retrieve the content of the given path
     * @param   mixed   $root   The specified root path
     * @return  array   All contents in an array
     */
    public function getContents() {
        $plugins = $this->getPlugins('OnLoad', $this->config);
        if ($plugins === false) { // strict detection
            return false;
        }

        $dirContents = array();
        if (!empty($this->config['getDir'])) {
            $dirContents = $this->_getDirContents($this->config['getDir']);
            if (!$dirContents) {
                $dirContents = array();
            }
        }
        $fileContents = array();
        if (!empty($this->config['getFile'])) {
            $fileContents = $this->_getFileContents($this->config['getFile']);
            if (!$fileContents) {
                $fileContents = array();
            }
        }
        $mergedContents = array();
        $mergedContents = array_merge($dirContents, $fileContents);
        $mergedContents = $this->_checkDuplication($mergedContents);
        $mergedContents = $this->_getDescription($mergedContents);
        $mergedContents = $this->_sortOrder($mergedContents);

        return $mergedContents;
    }

    /**
     * Existed description from the chunk of the &chkDesc parameter
     * @param array $contents
     */
    private function _getDescription(array $contents) {
        if (empty($contents)) {
            return $contents;
        }

        if (empty($this->config['chkDesc'])) {
            foreach ($contents as $key => $file) {
                $contents[$key]['description'] = '';
            }
            return $contents;
        }

        $chunkContent = $this->modx->getChunk($this->config['chkDesc']);

        $linesX = @explode('||', $chunkContent);
        array_walk($linesX, create_function('&$val', '$val = trim($val);'));
        foreach ($linesX as $k => $v) {
            if (empty($v)) {
                unset($linesX[$k]);
                continue;
            }
            $descX = @explode('|', $v);
            array_walk($descX, create_function('&$val', '$val = trim($val);'));

            $phsReplaced = $this->replacePropPhs($descX[0]);
            $realPath = realpath($phsReplaced);

            if (!$realPath) {
                continue;
            }

            $desc[$realPath] = $descX[1];
        }

        foreach ($contents as $key => $file) {
            $contents[$key]['description'] = '';
            if (isset($desc[$file['fullPath']])) {
                $contents[$key]['description'] = $desc[$file['fullPath']];
            }
        }

        return $contents;
    }

    /**
     * Get dynamic file's basepath
     * @param   string  $filename   file's name
     * @return  string
     */
    public function getBasePath($filename) {
        if (!empty($this->mediaSource)) {
            if (method_exists($this->mediaSource, 'getBasePath')) {
                return $this->mediaSource->getBasePath($filename);
            } elseif (method_exists($this->mediaSource, 'getBaseUrl')) {
                return $this->mediaSource->getBaseUrl();
            }
        }
        return false;
    }

    /**
     * Check the called file contents with the registered database.
     * If it's not listed, auto save
     * @param   array   $file       Realpath filename / dirname
     * @param   boolean $autoCreate Auto create database if it doesn't exist
     * @return  void
     */
    private function _checkDb(array $file = array(), $autoCreate = true) {
        if (empty($file)) {
            return false;
        }

        if (empty($this->mediaSource)) {
            $realPath = realpath($file['filename']);
            if (empty($realPath)) {
                return false;
            }
        } else {
            $search = $this->getBasePath($file['filename']);
            if (!empty($search)) {
                $file['filename'] = str_replace($search, '', $file['filename']);
            }
        }

//        $filename = $this->utfEncoder($file['filename']);
        $filename = $file['filename'];

        $fdlPath = $this->modx->getObject('fdPaths', array(
            'ctx' => $file['ctx'],
            'media_source_id' => $this->config['mediaSourceId'],
            'filename' => $filename,
            'hash' => $this->_setHashedParam($file['ctx'], $file['filename'])
        ));
        if (!$fdlPath) {
            if (!$autoCreate) {
                return false;
            }
            $fdlPath = $this->modx->newObject('fdPaths');
            $fdlPath->fromArray(array(
                'ctx' => $file['ctx'],
                'media_source_id' => $this->config['mediaSourceId'],
                'filename' => $filename,
                'count' => 0,
                'hash' => $this->_setHashedParam($file['ctx'], $file['filename'])
            ));
            if ($fdlPath->save() === false) {
                $msg = $this->modx->lexicon($this->config['prefix'] . 'err_save_counter');
                $this->setError($msg);
                $this->modx->log(modX::LOG_LEVEL_ERROR, '[FileDownloadR] ' . $msg, '', __METHOD__, __FILE__, __LINE__);
                return false;
            }
        }
        $checked = $fdlPath->toArray();
        $checked['count'] = (int) $this->modx->getCount('fdDownloads', array('path_id' => $fdlPath->getPrimaryKey()));

        return $checked;
    }

    /**
     * Check any duplication output
     * @param   array   $mergedContents merging the &getDir and &getFile result
     * @return  array   Unique filenames
     */
    private function _checkDuplication(array $mergedContents) {
        if (empty($mergedContents)) {
            return $mergedContents;
        }

        $this->_count['dirs'] = 0;
        $this->_count['files'] = 0;

        $c = array();
        $d = array();
        foreach ($mergedContents as $content) {
            if (isset($c[$content['fullPath']]))
                continue;

            $c[$content['fullPath']] = $content;
            $d[] = $content;

            if ($content['type'] === 'dir') {
                $this->_count['dirs'] ++;
            } else {
                $this->_count['files'] ++;
            }
        }

        return $d;
    }

    /**
     * Count the numbers retrieved objects (dirs/files)
     * @param   string  $subject    the specified subject
     * @return  int     number of the subject
     */
    public function countContents($subject) {
        if ($subject === 'dirs') {
            return $this->_count['dirs'];
        } elseif ($subject === 'files') {
            return $this->_count['files'];
        } else {
            return intval(0);
        }
    }

    /**
     * Unicode character encoding work around.<br />
     * For human reading.<br />
     * The value is set from the module's config page.
     *
     * @link http://a4esl.org/c/charset.html
     * @param   string  $text           the string to be encoded
     * @param   string  $callback       call back function
     * @param   string  $callbackParams call back parameters
     * @return  string  returns the encoding
     */
    public function utfEncoder($text, $callback = false, $callbackParams = array()) {
        $convertedText = $text;

        if (strtoupper($this->config['encoding']) == 'NONE') {
            if ($callback !== false) {
                $callbackParams = array_merge(array($text), $callbackParams);
                $convertedText = call_user_func($callback, $callbackParams);
            } else {
                $convertedText = $text;
            }
        }

//        if ($this->config['encoding'] == 'UTF-8') {
//            if ($callback !== false && $callback != 'ucfirst') {
//                $callbackParams = array_merge($text, $callbackParams);
//                $convertedText = call_user_func($callback, $callbackParams);
//            } elseif ($callback == 'ucfirst') {
//                // http://bytes.com/topic/php/answers/444382-ucfirst-utf-8-setlocale#post1693669
//                $fc = mb_strtoupper(mb_substr($text, 0, 1, 'UTF-8'), 'UTF-8');
//                $convertedText = $fc . mb_substr($text, 1, mb_strlen($text, 'UTF-8'), 'UTF-8');
//            } else {
//                $convertedText = utf8_encode($text);
//            }
//        }
//
//        if ($this->config['encoding'] == 'UTF-8 (Rin)') {
        if (strtoupper($this->config['encoding']) == 'UTF-8') {
            $convertedText = $this->utfEncoderRin($text, $callback, $callbackParams);
        }

        return $convertedText;
    }

    /**
     * Unicode character decoding work around.<br />
     * For file system reading.<br />
     * The value is set from the module's config page.
     *
     * @link http://a4esl.org/c/charset.html
     * @param   string  $text           the string to be decoded
     * @param   string  $callback       call back function
     * @param   string  $callbackParams call back parameters
     * @return  string  returns the decoding
     */
    public function utfDecoder($text, $callback = false, $callbackParams = array()) {
        $convertedText = $text;

        if (strtoupper($this->config['encoding']) == 'NONE') {
            if ($callback !== false) {
                $callbackParams = array_merge(array($text), $callbackParams);
                $convertedText = call_user_func($callback, $callbackParams);
            } else {
                $convertedText = $text;
            }
        }

//        if ($this->config['encoding'] == 'UTF-8') {
//            if ($callback !== false) {
//                $callbackParams = array_merge($text, $callbackParams);
//                $convertedText = call_user_func($callback, $callbackParams);
//            } else {
//                $convertedText = utf8_decode($text);
//            }
//        }
//
//        if ($this->config['encoding'] == 'UTF-8 (Rin)') {
        if (strtoupper($this->config['encoding']) == 'UTF-8') {
            $convertedText = $this->utfDecoderRin($text, $callback, $callbackParams);
        }

        return $convertedText;
    }

    /**
     * Load UTF-8 Class
     * @param   string  $callback       method's name
     * @param   array   $callbackParams call back parameters (in an array)
     * @author  Rin
     * @link    https://github.com/rin-nas/php5-utf8
     * @return  string  converted text
     */
    public function utfRin($callback, array $callbackParams = array()) {
        include_once($this->config['modelPath'] . 'php5-utf8/UTF8.php');
        include_once($this->config['modelPath'] . 'php5-utf8/ReflectionTypeHint.php');

        $utf = call_user_func_array(array('UTF8', $callback), $callbackParams);
        return $utf;
    }

    /**
     * Encoding using the class from
     * @author  Rin <http://forum.dklab.ru/profile.php?mode=viewprofile&u=3940>
     * @link    https://github.com/rin-nas/php5-utf8
     * @param   string  $text           text to be converted
     * @param   string  $callback       call back function's name
     * @param   array   $callbackParams call back parameters (in an array)
     * @return  string  converted text
     */
    public function utfEncoderRin($text, $callback = false, $callbackParams = array()) {
        include_once($this->config['modelPath'] . 'php5-utf8/UTF8.php');
        include_once($this->config['modelPath'] . 'php5-utf8/ReflectionTypeHint.php');
        $convertedText = $text;

        $mbDetectEncoding = mb_detect_encoding($text);
        if ($callback !== false) {
            $callbackParams = array_merge(array($text), $callbackParams);
            $convertedText = call_user_func_array(array('UTF8', $callback), $callbackParams);
        } else {
            // fixedmachine -- http://modxcms.com/forums/index.php/topic,49266.msg292206.html#msg292206
            $convertedText = UTF8::convert_to($text, $mbDetectEncoding);
        }

        return $convertedText;
    }

    /**
     * Decoding using the class from
     * @author  Rin <http://forum.dklab.ru/profile.php?mode=viewprofile&u=3940>
     * @link    https://github.com/rin-nas/php5-utf8
     * @param   string  $text           text to be converted
     * @param   string  $callback       call back function's name
     * @param   array   $callbackParams call back parameters (in an array)
     * @return  string  converted text
     */
    public function utfDecoderRin($text, $callback = false, $callbackParams = array()) {
        include_once($this->config['modelPath'] . 'php5-utf8/UTF8.php');
        include_once($this->config['modelPath'] . 'php5-utf8/ReflectionTypeHint.php');
        $convertedText = $text;

        $mbDetectEncoding = mb_detect_encoding($text);
        if ($callback !== false) {
            $callbackParams = array_merge(array($text), $callbackParams);
            $convertedText = call_user_func_array(array('UTF8', $callback), $callbackParams);
        } elseif (!$mbDetectEncoding || ($mbDetectEncoding != 'ASCII' && $mbDetectEncoding != 'UTF-8')) {
            // fixedmachine -- http://modxcms.com/forums/index.php/topic,49266.msg292206.html#msg292206
            $convertedText = UTF8::convert_from($text, "ASCII");
        } else {
            $convertedText = UTF8::convert_from($text, $mbDetectEncoding);
//            $convertedText = utf8_decode($text);
        }

        return $convertedText;
    }

    /**
     * Retrieve the content of the given directory path
     * @param   array   $paths      The specified root path
     * @return  array   Dir's contents in an array
     */
    private function _getDirContents(array $paths = array()) {
        if (empty($paths)) {
            return false;
        }

        $contents = array();
        foreach ($paths as $rootPath) {
            if (empty($this->mediaSource)) {
                $rootRealPath = realpath($rootPath);
                if (!is_dir($rootPath) || empty($rootRealPath)) {
                    $this->modx->log(modX::LOG_LEVEL_ERROR, '&getDir parameter expects a correct dir path. <b>"' . $rootPath . '"</b> is given.', '', __METHOD__, __FILE__, __LINE__);
                    return false;
                }
            }

            $plugins = $this->getPlugins('BeforeDirOpen', array(
                'dirPath' => $rootPath,
            ));

            if ($plugins === false) { // strict detection
                return false;
            } elseif ($plugins === 'continue') {
                continue;
            }

            if (empty($this->mediaSource)) {
                $scanDir = scandir($rootPath);

                $excludes = $this->modx->getOption('filedownloadr.exclude_scan', $this->config, '.,..,Thumbs.db,.htaccess,.htpasswd,.ftpquota,.DS_Store');
                $excludes = array_map('trim', @explode(',', $excludes));
                foreach ($scanDir as $file) {
                    if (in_array($file, $excludes)) {
                        continue;
                    }

                    $fullPath = $rootRealPath . $this->ds . $file;
                    $fileType = @filetype($fullPath);

                    if ($fileType == 'file') {
                        $fileInfo = $this->_fileInformation($fullPath);
                        if (!$fileInfo) {
                            continue;
                        }
                        $contents[] = $fileInfo;
                    } elseif ($this->config['browseDirectories']) {
                        // a directory
                        $cdb = array();
                        $cdb['ctx'] = $this->modx->context->key;
                        $cdb['filename'] = $fullPath . $this->ds;
                        $cdb['hash'] = $this->_getHashedParam($cdb['ctx'], $cdb['filename']);

                        $checkedDb = $this->_checkDb($cdb);
                        if (!$checkedDb) {
                            continue;
                        }

                        $notation = $this->_aliasName($file);
                        $alias = $notation[1];

                        $unixDate = filemtime($fullPath);
                        $date = date($this->config['dateFormat'], $unixDate);
                        $link = $this->_linkDirOpen($checkedDb['hash'], $checkedDb['ctx']);

                        $imgType = $this->_imgType('dir');
                        $dir = array(
                            'ctx' => $checkedDb['ctx'],
                            'fullPath' => $this->utfEncoder($fullPath),
                            'path' => $this->utfEncoder($rootRealPath),
                            'filename' => $this->utfEncoder($file),
                            'alias' => $this->utfEncoder($alias),
                            'type' => $fileType,
                            'ext' => '',
                            'size' => '',
                            'sizeText' => '',
                            'unixdate' => $unixDate,
                            'date' => $date,
                            'image' => $this->config['imgLocat'] . $imgType,
                            'count' => (int) $this->modx->getCount('fdDownloads', array('path_id' => $checkedDb['id'])),
                            'link' => $link['url'], // fallback
                            'url' => $link['url'],
                            'hash' => $checkedDb['hash']
                        );

                        $contents[] = $dir;
                    }
                }
            } else {
                $scanDir = $this->mediaSource->getContainerList($rootPath);

                $excludes = $this->modx->getOption('filedownloadr.exclude_scan', $this->config, '.,..,Thumbs.db,.htaccess,.htpasswd,.ftpquota,.DS_Store');
                $excludes = array_map('trim', @explode(',', $excludes));
                foreach ($scanDir as $file) {
                    if (in_array(($file['text']), $excludes)) {
                        continue;
                    }

                    $fullPath = $file['id'];

                    if ($file['type'] == 'file') {
                        $fileInfo = $this->_fileInformation($fullPath);
                        if (!$fileInfo) {
                            continue;
                        }

                        $contents[] = $fileInfo;
                    } elseif ($this->config['browseDirectories']) {
                        // a directory
                        $cdb = array();
                        $cdb['ctx'] = $this->modx->context->key;
                        $cdb['filename'] = $fullPath;
                        $cdb['hash'] = $this->_getHashedParam($cdb['ctx'], $cdb['filename']);

                        $checkedDb = $this->_checkDb($cdb);
                        if (!$checkedDb) {
                            continue;
                        }

                        $notation = $this->_aliasName($file['name']);
                        $alias = $notation[1];

                        if (method_exists($this->mediaSource, 'getBasePath')) {
                            $rootRealPath = $this->mediaSource->getBasePath($rootPath) . $rootPath;
                            $unixDate = filemtime(realpath($rootRealPath));
                        } elseif (method_exists($this->mediaSource, 'getObjectUrl')) {
                            $rootRealPath = $this->mediaSource->getObjectUrl($rootPath);
                            $unixDate = filemtime($rootRealPath);
                        } else {
                            $rootRealPath = realpath($rootPath);
                            $unixDate = filemtime($rootRealPath);
                        }

                        $date = date($this->config['dateFormat'], $unixDate);
                        $link = $this->_linkDirOpen($checkedDb['hash'], $checkedDb['ctx']);

                        $imgType = $this->_imgType('dir');
                        $dir = array(
                            'ctx' => $checkedDb['ctx'],
                            'fullPath' => $this->utfEncoder($fullPath),
                            'path' => $this->utfEncoder($rootRealPath),
                            'filename' => $this->utfEncoder($this->_basename($fullPath)),
                            'alias' => $this->utfEncoder($alias),
                            'type' => 'dir',
                            'ext' => '',
                            'size' => '',
                            'sizeText' => '',
                            'unixdate' => $unixDate,
                            'date' => $date,
                            'image' => $this->config['imgLocat'] . $imgType,
                            'count' => (int) $this->modx->getCount('fdDownloads', array('path_id' => $checkedDb['id'])),
                            'link' => $link['url'], // fallback
                            'url' => $link['url'],
                            'hash' => $checkedDb['hash']
                        );

                        $contents[] = $dir;
                    }
                }
            }

            $plugins = $this->getPlugins('AfterDirOpen', array(
                'dirPath' => $rootPath,
                'contents' => $contents,
            ));

            if ($plugins === false) { // strict detection
                return false;
            } elseif ($plugins === 'continue') {
                continue;
            }
        }

        return $contents;
    }

    /**
     * Retrieve the content of the given file path
     * @param   array   $paths   The specified file path
     * @return  array   File contents in an array
     */
    private function _getFileContents(array $paths = array()) {
        $contents = array();
        foreach ($paths as $fileRow) {
            $fileInfo = $this->_fileInformation($fileRow);
            if (!$fileInfo) {
                continue;
            }
            $contents[] = $fileInfo;
        }

        return $contents;
    }

    /**
     * Retrieves the required information from a file
     * @param   string  $file   absoulte file path or a file with an [| alias]
     * @return  array   All about the file
     */
    private function _fileInformation($file) {
        $notation = $this->_aliasName($file);
        $path = $notation[0];
        $alias = $notation[1];

        if (empty($this->mediaSource)) {
            $fileRealPath = realpath($path);
            if (!is_file($fileRealPath) || !$fileRealPath) {
                // @todo: lexicon
                $this->modx->log(modX::LOG_LEVEL_ERROR, '&getFile parameter expects a correct file path. ' . $path . ' is given.', '', __METHOD__, __FILE__, __LINE__);
                return false;
            }
            $baseName = $this->_basename($fileRealPath);
            $size = filesize($fileRealPath);
            $type = @filetype($fileRealPath);
        } else {
            if (method_exists($this->mediaSource, 'getBasePath')) {
                $fileRealPath = $this->mediaSource->getBasePath($path) . $path;
            } elseif (method_exists($this->mediaSource, 'getObjectUrl')) {
                $fileRealPath = $this->mediaSource->getObjectUrl($path);
            } else {
                $fileRealPath = realpath($path);
            }
            $baseName = $this->_basename($fileRealPath);
            if (method_exists($this->mediaSource, 'getObjectFileSize')) {
                $size = $this->mediaSource->getObjectFileSize($path);
            } else {
                $size = filesize(realpath($fileRealPath));
            }
            $type = @filetype($fileRealPath);
        }

        $xBaseName = explode('.', $baseName);
        $tempExt = end($xBaseName);
        $ext = strtolower($tempExt);
        $imgType = $this->_imgType($ext);

        if (!$this->_isExtShown($ext)) {
            return false;
        }
        if ($this->_isExtHidden($ext)) {
            return false;
        }

        $cdb = array();
        $cdb['ctx'] = $this->modx->context->key;
        $cdb['filename'] = $fileRealPath;
        $cdb['hash'] = $this->_getHashedParam($cdb['ctx'], $cdb['filename']);

        $checkedDb = $this->_checkDb($cdb);
        if (!$checkedDb) {
            return false;
        }

        if ($this->config['directLink']) {
            $link = $this->_directLinkFileDownload($this->utfDecoder($checkedDb['filename']));
            if (!$link) {
                return false;
            }
        } else {
            $link = $this->_linkFileDownload($checkedDb['filename'], $checkedDb['hash'], $checkedDb['ctx']);
        }

        $unixDate = filemtime($fileRealPath);
        $date = date($this->config['dateFormat'], $unixDate);
        $info = array(
            'ctx' => $checkedDb['ctx'],
            'fullPath' => $fileRealPath,
            'path' => $this->utfEncoder(dirname($fileRealPath)),
            'filename' => $this->utfEncoder($baseName),
            'alias' => $alias,
            'type' => $type,
            'ext' => $ext,
            'size' => $size,
            'sizeText' => $this->_fileSizeText($size),
            'unixdate' => $unixDate,
            'date' => $date,
            'image' => $this->config['imgLocat'] . $imgType,
            'count' => (int) $this->modx->getCount('fdDownloads', array('path_id' => $checkedDb['id'])),
            'link' => $link['url'], // fallback
            'url' => $link['url'],
            'hash' => $checkedDb['hash']
        );

        return $info;
    }

    /**
     * Get the alias/description from the pipe ( "|" ) symbol on the snippet
     * @param   string  $path   the full path
     * @return  array   [0] => the path [1] => the alias name
     */
    private function _aliasName($path) {
        $xPipes = @explode('|', $path);
        $notation = array();
        $notation[0] = trim($xPipes[0]);
        $notation[1] = !isset($xPipes[1]) ? '' : trim($xPipes[1]);

        return $notation;
    }

    /**
     * Custom basename, because PHP's basename can not read Chinese characters
     * @param   string  $path   full path
     */
    private function _basename($path) {
        $parts = @explode($this->ds, $path);
        $parts = array_reverse($parts);

        return $parts[0];
    }

    /**
     * Get the right image type to the specified file's extension, or fall back
     * to the default image.
     * @param string $ext
     * @return type
     */
    private function _imgType($ext) {
        return isset($this->_imgType[$ext]) ? $this->_imgType[$ext] : (isset($this->_imgType['default']) ? $this->_imgType['default'] : false);
    }

    /**
     * Retrieve the images for the specified file extensions
     * @return  array   file type's images
     */
    private function _imgTypeProp() {
        if (empty($this->config['imgTypes'])) {
            return false;
        }
        $fdImagesChunk = $this->parseTpl($this->config['imgTypes']);
        $fdImagesChunkX = @explode(',', $fdImagesChunk);
        $imgType = array();
        foreach ($fdImagesChunkX as $v) {
            $typeX = @explode('=', $v);
            $imgType[strtolower(trim($typeX[0]))] = trim($typeX[1]);
        }

        return $imgType;
    }

    /**
     * @todo _linkFileDownload: change the hard coded html to template
     * @param   string  $filePath   file's path
     * @param   string  $hash       hash
     * @param   string  $ctx        specifies a context to limit URL generation to.
     * @return  array   the download link and the javascript's attribute
     */
    private function _linkFileDownload($filePath, $hash, $ctx = 'web') {
        $link = array();
        if ($this->config['noDownload']) {
            $link['url'] = $filePath;
        } else {
            $queries = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
            $existingArgs = array();
            if (!empty($queries)) {
                $queries = @explode('&', $queries);
                foreach ($queries as $query) {
                    $xquery = @explode('=', $query);
                    $existingArgs[$xquery[0]] = !empty($xquery[1]) ? $xquery[1] : '';
                }
            }
            $args = array();
            if (!empty($existingArgs)) {
                unset($existingArgs['id']);
                foreach ($existingArgs as $k => $v) {
                    $args[] = $k . '=' . $v;
                }
            }
            $args[] = 'fdlfile=' . $hash;
            $url = $this->modx->makeUrl($this->modx->resource->get('id'), $ctx, @implode('&', $args));
            $link['url'] = $url;
        }
        $link['hash'] = $hash;
        return $link;
    }

    /**
     * Set the direct link to the file path
     * @param   string  $filePath   absolute file path
     * @return  array   the download link and the javascript's attribute
     */
    private function _directLinkFileDownload($filePath) {
        $link = array();
        $link['url'] = '';
        if ($this->config['noDownload']) {
            $link['url'] = $filePath;
        } else {
            // to use this method, the file should always be placed on the web root
            $corePath = str_replace('/', $this->ds, MODX_CORE_PATH);
            if (stristr($filePath, $corePath)) {
                return false;
            }
            // switching from absolute path to url is nuts
            if (empty($this->mediaSource)) {
                $fileUrl = str_ireplace(MODX_BASE_PATH, MODX_SITE_URL, $filePath);
                $fileUrl = str_replace($this->ds, '/', $fileUrl);
                $parseUrl = parse_url($fileUrl);
                $url = ltrim($parseUrl['path'], '/' . MODX_HTTP_HOST);
                $link['url'] = MODX_URL_SCHEME . MODX_HTTP_HOST . '/' . $url;
            } else {
                if (method_exists($this->mediaSource, 'getObjectUrl')) {
                    $link['url'] = $this->mediaSource->getObjectUrl($filePath);
                }
            }
        }
        $link['hash'] = '';
        return $link;
    }

    /**
     * @todo _linkDirOpen: change the hard coded html to template
     * @param   string  $hash       hash
     * @param   string  $ctx        specifies a context to limit URL generation to.
     * @return  array   the open directory link and the javascript's attribute
     */
    private function _linkDirOpen($hash, $ctx = 'web') {
        if (!$this->config['browseDirectories']) {
            return false;
        }
        $queries = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
        $existingArgs = array();
        if (!empty($queries)) {
            $queries = @explode('&', $queries);
            foreach ($queries as $query) {
                $xquery = @explode('=', $query);
                $existingArgs[$xquery[0]] = !empty($xquery[1]) ? $xquery[1] : '';
            }
        }
        $args = array();
        if (!empty($existingArgs)) {
            unset($existingArgs['id']);
            foreach ($existingArgs as $k => $v) {
                $args[] = $k . '=' . $v;
            }
        }
        $args[] = 'fdldir=' . $hash;
        if (!empty($this->config['fdlid'])) {
            $args[] = 'fdlid=' . $this->config['fdlid'];
        }
        $url = $this->modx->makeUrl($this->modx->resource->get('id'), $ctx, @implode('&', $args));
        $link = array();
        $link['url'] = $url;
        $link['hash'] = $hash;
        return $link;
    }

    /**
     * Set the new value to the getDir property to browse inside the clicked
     * directory
     * @param   string  $hash       the hashed link
     * @param   bool    $selected   to patch multiple snippet call
     * @return  bool    true | false
     */
    public function setDirProp($hash, $selected = true) {
        if (empty($hash) || !$selected) {
            return false;
        }
        $fdlPath = $this->modx->getObject('fdPaths', array('hash' => $hash));
        if (!$fdlPath) {
            return false;
        }
        $ctx = $fdlPath->get('ctx');
        if ($this->modx->context->key !== $ctx) {
            return false;
        }
        $path = $fdlPath->get('filename');
        $this->config['getDir'] = array($path);
        $this->config['getFile'] = array();

        return true;
    }

    /**
     * Download action
     * @param   string  $hash   hashed text
     * @return  void    file is pulled to the browser
     */
    public function downloadFile($hash) {
        if (empty($hash)) {
            return false;
        }
        $fdlPath = $this->modx->getObject('fdPaths', array('hash' => $hash));
        if (!$fdlPath) {
            return false;
        }
        $ctx = $fdlPath->get('ctx');
        if ($this->modx->context->key !== $ctx) {
            return false;
        }
        $mediaSourceId = $fdlPath->get('media_source_id');
        if (intval($this->config['mediaSourceId']) !== $mediaSourceId) {
            return false;
        }
        $filePath = $this->utfDecoder($fdlPath->get('filename'));
        $plugins = $this->getPlugins('BeforeFileDownload', array(
            'hash' => $hash,
            'ctx' => $ctx,
            'media_source_id' => $mediaSourceId,
            'filePath' => $filePath,
            'count' => (int) $this->modx->getCount('fdDownloads', array('path_id' => $fdlPath->get('id'))),
        ));
        if ($plugins === false) { // strict detection
            return false;
        }
        $fileExists = false;
        $filename = $this->_basename($filePath);
        if (empty($this->mediaSource)) {
            if (file_exists($filePath)) {
                $fileExists = true;
            }
        } else {
            if (method_exists($this->mediaSource, 'getBasePath')) {
                $filePath = $this->mediaSource->getBasePath($filePath) . $filePath;
                if (file_exists($filePath)) {
                    $fileExists = true;
                } else {
                    $fileExists = false;
                }
            } elseif (method_exists($this->mediaSource, 'getBaseUrl')) {
                $this->mediaSource->getObjectUrl($filePath);
                $content = @file_get_contents(urlencode($this->mediaSource->getObjectUrl($filePath)));
                if (!empty($content)) {
                    $pathParts = pathinfo($filename);
                    $temp = tempnam(sys_get_temp_dir(), 'fdl_' . time() . '_' . $pathParts['filename'] . '_');
                    $handle = fopen($temp, "r+b");
                    fwrite($handle, $content);
                    fseek($handle, 0);
                    fclose($handle);
                    $filePath = $temp;
                    $fileExists = true;
                } else {
                    $msg = 'Unable to get the content from remote server';
                    $this->setError($msg);
                    $this->modx->log(modX::LOG_LEVEL_ERROR, '[FileDownloadR] ' . $msg, '', __METHOD__, __FILE__, __LINE__);
                }
            } else {
                $fileExists = false;
            }
        }
        if ($fileExists) {
            // required for IE
            if (ini_get('zlib.output_compression')) {
                ini_set('zlib.output_compression', 'Off');
            }

            @set_time_limit(300);
            @ini_set('magic_quotes_runtime', 0);
            ob_end_clean(); //added to fix ZIP file corruption
            ob_start(); //added to fix ZIP file corruption

            header('Pragma: public');  // required
            header('Expires: 0');  // no cache
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Cache-Control: private', false);
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($filePath)) . ' GMT');
            header('Content-Description: File Transfer');
            header('Content-Type:'); //added to fix ZIP file corruption
            header('Content-Type: "application/force-download"');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . (string) (filesize($filePath))); // provide file size
            header('Connection: close');
            sleep(1);

            //Close the session to allow for header() to be sent
            session_write_close();
            ob_flush();
            flush();

            $chunksize = 1 * (1024 * 1024); // how many bytes per chunk
            $buffer = '';
            $handle = @fopen($filePath, 'rb');
            if ($handle === false) {
                return false;
            }
            while (!feof($handle) && connection_status() == 0) {
                $buffer = @fread($handle, $chunksize);
                if (!$buffer) {
                    die();
                }
                echo $buffer;
                ob_flush();
                flush();
            }
            fclose($handle);
            if (!empty($temp)) {
                @unlink($temp);
            }
            if ($this->config['countDownloads']) {
                $this->_setDownloadCount($hash);
            }

            // just run this away, it doesn't matter if the return is false
            $this->getPlugins('AfterFileDownload', array(
                'hash' => $hash,
                'ctx' => $ctx,
                'media_source_id' => $mediaSourceId,
                'filePath' => $filePath,
                'count' => (int) $this->modx->getCount('fdDownloads', array('path_id' => $fdlPath->get('id'))),
            ));

            exit;
        }

        return false;
    }

    /**
     * Add download counter
     * @param   string  $hash   secret hash
     * @return  boolean
     */
    private function _setDownloadCount($hash) {
        if (!$this->config['countDownloads']) {
            return false;
        }
        $fdlPath = $this->modx->getObject('fdPaths', array('hash' => $hash));
        if (!$fdlPath) {
            return false;
        }
        // save the new count
        $fdDownload = $this->modx->newObject('fdDownloads');
        $fdDownload->set('path_id', $fdlPath->getPrimaryKey());
        $fdDownload->set('referer', urldecode($_SERVER['HTTP_REFERER']));
        $fdDownload->set('user', $this->modx->user->get('id'));
        $fdDownload->set('timestamp', time());
        if (!empty($this->config['useGeolocation']) && !empty($this->config['geoApiKey'])) {
            require_once $this->config['modelPath'] . 'ipinfodb/ipInfo.inc.php';
            if (class_exists('ipInfo')) {
                $ipInfo = new ipInfo($this->config['geoApiKey'], 'json');
                $userIP = $ipInfo->getIPAddress();
                $location = $ipInfo->getCity($userIP);
                if (!empty($location)) {
                    $location = json_decode($location, true);
                    $fdDownload->set('ip', $location['ipAddress']);
                    $fdDownload->set('country', $location['countryCode']);
                    $fdDownload->set('region', $location['regionName']);
                    $fdDownload->set('city', $location['cityName']);
                    $fdDownload->set('zip', $location['zipCode']);
                    $fdDownload->set('geolocation', json_encode(array(
                        'latitude' => $location['latitude'],
                        'longitude' => $location['longitude'],
                    )));
                }
            }
        }
        if ($fdDownload->save() === false) {
            $msg = $this->modx->lexicon($this->config['prefix'] . 'err_save_counter');
            $this->setError($msg);
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[FileDownloadR] ' . $msg, '', __METHOD__, __FILE__, __LINE__);
            return false;
        }
    }

    /**
     * Get the download counting for the specified file and context
     * @param type $ctx
     * @param type $filePath
     * @return type
     * @deprecated since 2.0.0-beta1
     */
    private function _getDownloadCount($ctx, $filePath) {
        $fdlPath = $this->modx->getObject('fdPaths', array(
            'ctx' => $ctx,
            'filename' => $filePath
        ));
        if (!$fdlPath) {
            return 0;
        }

        return $fdlPath->get('count');
    }

    /**
     * Check whether the file with the specified extension is hidden from the list
     * @param   string  $ext    file's extension
     * @return  bool    true | false
     */
    private function _isExtHidden($ext) {
        if (empty($this->config['extHidden'])) {
            return false;
        }
        $extHiddenX = @explode(',', $this->config['extHidden']);
        array_walk($extHiddenX, create_function('&$val', '$val = strtolower(trim($val));'));
        if (!in_array($ext, $extHiddenX)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check whether the file with the specified extension is shown to the list
     * @param   string  $ext    file's extension
     * @return  bool    true | false
     */
    private function _isExtShown($ext) {
        if (empty($this->config['extShown'])) {
            return true;
        }
        $extShownX = @explode(',', $this->config['extShown']);
        array_walk($extShownX, create_function('&$val', '$val = strtolower(trim($val));'));
        if (in_array($ext, $extShownX)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check the user's group
     * @param   void
     * @return  bool    true | false
     */
    public function isAllowed() {
        if (empty($this->config['userGroups'])) {
            return true;
        } else {
            $userGroupsX = @explode(',', $this->config['userGroups']);
            array_walk($userGroupsX, create_function('&$val', '$val = trim($val);'));
            $userAccessGroupNames = $this->_userAccessGroupNames();

            $intersect = array_uintersect($userGroupsX, $userAccessGroupNames, "strcasecmp");

            if (count($intersect) > 0) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Get logged in usergroup names
     * @return  array   access group names
     */
    private function _userAccessGroupNames() {
        $userAccessGroupNames = array();

        $userId = $this->modx->user->get('id');
        if (empty($userId)) {
            return $userAccessGroupNames;
        }

        $userObj = $this->modx->getObject('modUser', $userId);
        $userGroupObj = $userObj->getMany('UserGroupMembers');
        foreach ($userGroupObj as $uGO) {
            $userGroupNameObj = $this->modx->getObject('modUserGroup', $uGO->get('user_group'));
            $userAccessGroupNames[] = $userGroupNameObj->get('name');
        }

        return $userAccessGroupNames;
    }

    /**
     * Prettify the file size with thousands unit byte
     * @param   int     $fileSize filesize()
     * @return  string  the pretty number
     */
    private function _fileSizeText($fileSize) {
        if ($fileSize === 0) {
            $returnVal = '0 bytes';
        } else if ($fileSize > 1024 * 1024 * 1024) {
            $returnVal = (ceil($fileSize / (1024 * 1024 * 1024) * 100) / 100) . ' GB';
        } else if ($fileSize > 1024 * 1024) {
            $returnVal = (ceil($fileSize / (1024 * 1024) * 100) / 100) . ' MB';
        } else if ($fileSize > 1024) {
            $returnVal = (ceil($fileSize / 1024 * 100) / 100) . ' kB';
        } else {
            $returnVal = $fileSize . ' B';
        }

        return $returnVal;
    }

    /**
     * Manage the order sorting by all sorting parameters:
     * - sortBy
     * - sortOrder
     * - sortOrderNatural
     * - sortByCaseSensitive
     * - browseDirectories
     * - groupByDirectory
     * @param   array   $contents   unsorted contents
     * @return  array   sorted contents
     */
    private function _sortOrder(array $contents) {
        if (empty($contents)) {
            return $contents;
        } else {
            $sort = $contents;
        }

        if (empty($this->config['groupByDirectory'])) {
            $sort = $this->_groupByType($contents);
        } else {
            $sortPath = array();
            foreach ($contents as $k => $file) {
                if (!$this->config['browseDirectories'] && $file['type'] === 'dir') {
                    continue;
                }
                $sortPath[$file['path']][$k] = $file;
            }

            $sort = array();
            foreach ($sortPath as $k => $path) {
                // path name for the &groupByDirectory template: tpl-group
                $this->_output['rows'] .= $this->_tplDirectory($k);

                $sort['path'][$k] = $this->_groupByType($path);
            }
        }

        return $sort;
    }

    /**
     * Grouping the contents by filetype
     * @param   array   $contents   contents
     * @return  array   grouped contents
     */
    private function _groupByType(array $contents) {
        if (empty($contents)) {
            return false;
        }

        $sortType = array();
        foreach ($contents as $k => $file) {
            if (empty($this->config['browseDirectories']) && $file['type'] === 'dir') {
                continue;
            }
            $sortType[$file['type']][$k] = $file;
        }
        if (empty($sortType)) {
            return false;
        }

        foreach ($sortType as $k => $file) {
            if (count($file) > 1) {
                $sortType[$k] = $this->_sortMultiOrders($file);
            }
        }

        $sort = array();
        $dirs = '';
        if (!empty($this->config['browseDirectories']) && !empty($sortType['dir'])) {
            $sort['dir'] = $sortType['dir'];
            // template
            $row = 1;
            foreach ($sort['dir'] as $k => $v) {
                $v['class'] = $this->_cssDir($row);
                $dirs .= $this->_tplDir($v);
                $row++;
            }
        }

        $phs = array();
        $phs[$this->config['prefix'] . 'classPath'] = (!empty($this->config['cssPath'])) ? ' class="' . $this->config['cssPath'] . '"' : '';
        $phs[$this->config['prefix'] . 'path'] = $this->_breadcrumbs();
        $this->_output['dirRows'] = '';
        if (!empty($this->config['tplWrapperDir']) && !empty($dirs)) {
            $phs[$this->config['prefix'] . 'dirRows'] = $dirs;
            $this->_output['dirRows'] .= $this->parseTpl($this->config['tplWrapperDir'], $phs);
        } else {
            $this->_output['dirRows'] .= $dirs;
        }

        $files = '';
        if (!empty($sortType['file'])) {
            $sort['file'] = $sortType['file'];
            // template
            $row = 1;
            foreach ($sort['file'] as $k => $v) {
                $v['class'] = $this->_cssFile($row, $v['ext']);
                $files .= $this->_tplFile($v);
                $row++;
            }
        }
        $this->_output['fileRows'] = '';
        if (!empty($this->config['tplWrapperFile']) && !empty($files)) {
            $phs[$this->config['prefix'] . 'fileRows'] = $files;
            $this->_output['fileRows'] .= $this->parseTpl($this->config['tplWrapperFile'], $phs);
        } else {
            $this->_output['fileRows'] .= $files;
        }

        $this->_output['rows'] .= $this->_output['dirRows'];
        $this->_output['rows'] .= $this->_output['fileRows'];

        return $sort;
    }

    /**
     * Multi dimensional sorting
     * @param   array   $array          content array
     * @param   string  $index          order index
     * @param   string  $order          asc [| void]
     * @param   bool    $natSort        true | false
     * @param   bool    $caseSensitive  true | false
     * @return  array   the sorted array
     * @link    modified from http://www.php.net/manual/en/function.sort.php#104464
     */
    private function _sortMultiOrders($array) {
        if (!is_array($array) || count($array) < 1) {
            return $array;
        }

        $temp = array();
        foreach (array_keys($array) as $key) {
            $temp[$key] = $array[$key][$this->config['sortBy']];
        }

        if ($this->config['sortOrderNatural'] != 1) {
            if (strtolower($this->config['sortOrder']) == 'asc') {
                asort($temp);
            } else {
                arsort($temp);
            }
        } else {
            if ($this->config['sortByCaseSensitive'] != 1) {
                natcasesort($temp);
            } else {
                natsort($temp);
            }
            if (strtolower($this->config['sortOrder']) != 'asc') {
                $temp = array_reverse($temp, true);
            }
        }

        $sorted = array();
        foreach (array_keys($temp) as $key) {
            if (is_numeric($key)) {
                $sorted[] = $array[$key];
            } else {
                $sorted[$key] = $array[$key];
            }
        }

        return $sorted;
    }

    /**
     * Generate the class names for the directory rows
     * @param   int     $row    the row number
     * @return  string  imploded class names
     */
    private function _cssDir($row) {
        $totalRow = $this->_count['dirs'];
        $cssName = array();
        if (!empty($this->config['cssDir'])) {
            $cssName[] = $this->config['cssDir'];
        }
        if (!empty($this->config['cssAltRow']) && $row % 2 === 1) {
            $cssName[] = $this->config['cssAltRow'];
        }
        if (!empty($this->config['cssFirstDir']) && $row === 1) {
            $cssName[] = $this->config['cssFirstDir'];
        } elseif (!empty($this->config['cssLastDir']) && $row === $totalRow) {
            $cssName[] = $this->config['cssLastDir'];
        }

        $o = '';
        $cssNames = @implode(' ', $cssName);
        if (!empty($cssNames)) {
            $o = ' class="' . $cssNames . '"';
        }

        return $o;
    }

    /**
     * Generate the class names for the file rows
     * @param   int     $row    the row number
     * @param   string  $ext    extension
     * @return  string  imploded class names
     */
    private function _cssFile($row, $ext) {
        $totalRow = $this->_count['files'];
        $cssName = array();
        if (!empty($this->config['cssFile'])) {
            $cssName[] = $this->config['cssFile'];
        }
        if (!empty($this->config['cssAltRow']) && $row % 2 === 1) {
            if ($this->_count['dirs'] % 2 === 0) {
                $cssName[] = $this->config['cssAltRow'];
            }
        }
        if (!empty($this->config['cssFirstFile']) && $row === 1) {
            $cssName[] = $this->config['cssFirstFile'];
        } elseif (!empty($this->config['cssLastFile']) && $row === $totalRow) {
            $cssName[] = $this->config['cssLastFile'];
        }
        if (!empty($this->config['cssExtension'])) {
            $cssNameExt = '';
            if (!empty($this->config['cssExtensionPrefix'])) {
                $cssNameExt .= $this->config['cssExtensionPrefix'];
            }
            $cssNameExt .= $ext;
            if (!empty($this->config['cssExtensionSuffix'])) {
                $cssNameExt .= $this->config['cssExtensionSuffix'];
            }
            $cssName[] = $cssNameExt;
        }
        $o = '';
        $cssNames = @implode(' ', $cssName);
        if (!empty($cssNames)) {
            $o = ' class="' . $cssNames . '"';
        }
        return $o;
    }

    /**
     * Parsing the directory template
     * @param   array   $contents   properties
     * @return  string  rendered HTML
     */
    private function _tplDir(array $contents) {
        if (empty($contents)) {
            return '';
        }
        foreach ($contents as $k => $v) {
            $phs[$this->config['prefix'] . $k] = $v;
        }
        $tpl = $this->parseTpl($this->config['tplDir'], $phs);

        return $tpl;
    }

    /**
     * Parsing the file template
     * @param   array   $fileInfo   properties
     * @return  string  rendered HTML
     */
    private function _tplFile(array $fileInfo) {
        if (empty($fileInfo) || empty($this->config['tplFile'])) {
            return '';
        }
        foreach ($fileInfo as $k => $v) {
            $phs[$this->config['prefix'] . $k] = $v;
        }
        $tpl = $this->parseTpl($this->config['tplFile'], $phs);

        return $tpl;
    }

    /**
     * Path template if &groupByDirectory is enabled
     * @param   string  $path   Path's name
     * @return  string  rendered HTML
     */
    private function _tplDirectory($path) {
        if (empty($path) || is_array($path)) {
            return '';
        }
        $phs[$this->config['prefix'] . 'class'] = (!empty($this->config['cssGroupDir'])) ? ' class="' . $this->config['cssGroupDir'] . '"' : '';
        $groupPath = str_replace($this->ds, $this->config['breadcrumbSeparator'], $this->_trimPath($path));
        $phs[$this->config['prefix'] . 'groupDirectory'] = $groupPath;
        $tpl = $this->parseTpl($this->config['tplGroupDir'], $phs);

        return $tpl;
    }

    /**
     * Wraps templates
     * @return  string  rendered template
     */
    private function _tplWrapper() {
        $phs[$this->config['prefix'] . 'classPath'] = (!empty($this->config['cssPath'])) ? ' class="' . $this->config['cssPath'] . '"' : '';
        $phs[$this->config['prefix'] . 'path'] = $this->_breadcrumbs();
        $rows = !empty($this->_output['rows']) ? $this->_output['rows'] : '';
        $phs[$this->config['prefix'] . 'rows'] = $rows;
        $phs[$this->config['prefix'] . 'dirRows'] = $this->_output['dirRows'];
        $phs[$this->config['prefix'] . 'fileRows'] = $this->_output['fileRows'];
        if (!empty($this->config['tplWrapper'])) {
            $tpl = $this->parseTpl($this->config['tplWrapper'], $phs);
        } else {
            $tpl = $rows;
        }

        return $tpl;
    }

    /**
     * Trim the absolute path to be a relatively safe path
     * @param   string  $path   the absolute path
     * @return  string  trimmed path
     */
    private function _trimPath($path) {
        $trimmedPath = $path;
        foreach ($this->config['origDir'] as $dir) {
            $dir = trim($dir, '/') . '/';
            $pattern = '`^(' . preg_quote($dir) . ')`';
            if (preg_match($pattern, $path)) {
                $trimmedPath = preg_replace($pattern, '', $path);
            }
            if (empty($this->mediaSource)) {
                $modxCorePath = realpath(MODX_CORE_PATH) . $this->ds;
                $modxAssetsPath = realpath(MODX_ASSETS_PATH) . $this->ds;
            } else {
                $modxCorePath = MODX_CORE_PATH;
                $modxAssetsPath = MODX_ASSETS_PATH;
            }
            if (false !== stristr($trimmedPath, $modxCorePath)) {
                $trimmedPath = str_replace($modxCorePath, '', $trimmedPath);
            } elseif (false !== stristr($trimmedPath, $modxAssetsPath)) {
                $trimmedPath = str_replace($modxAssetsPath, '', $trimmedPath);
            }
        }

        return $trimmedPath;
    }

    /**
     * Get absolute path of the given relative path, based on media source
     * @param   string  $path   relative path
     * @return  string  absolute path
     */
    private function _getAbsolutePath($path) {
        $output = '';
        if (empty($this->mediaSource)) {
            $output = realpath($path) . $this->ds;
        } else {
            if (method_exists($this->mediaSource, 'getBasePath')) {
                $output = $this->mediaSource->getBasePath($path) . trim($path, $this->ds) . $this->ds;
            } elseif (method_exists($this->mediaSource, 'getObjectUrl')) {
                $output = $this->mediaSource->getObjectUrl($path) . $this->ds;
            }
        }
        return $output;
    }

    /**
     * Create a breadcrumbs link
     * @param   void
     * @return  string  a breadcrumbs link
     */
    private function _breadcrumbs() {
        if (empty($this->config['browseDirectories'])) {
            return '';
        }
        $dirs = $this->config['getDir'];
        if (count($dirs) > 1) {
            return '';
        } else {
            $path = $dirs[0];
        }
        $trimmedPath = trim($path);
        $trimmedPath = trim($this->_trimPath($trimmedPath), $this->ds);
        $trimmedPath = trim($trimmedPath, $this->config['breadcrumbSeparator']);
        $basePath = str_replace($trimmedPath, '', $path);
        if ($basePath === '/') {
            $basePath = '';
        }
        if ($this->ds !== $this->config['breadcrumbSeparator']) {
            $pattern = '`[' . preg_quote($this->ds) . preg_quote($this->config['breadcrumbSeparator']) . ']+`';
        } else {
            $pattern = '`[' . preg_quote($this->ds) . ']+`';
        }
        $trimmedPathX = preg_split($pattern, $trimmedPath);
        $trailingPath = $basePath;
        $trail = array();
        $trailingLink = array();
        $countTrimmedPathX = count($trimmedPathX);
        foreach ($trimmedPathX as $k => $title) {
            $trailingPath .= $title . $this->ds;
            $absPath = $this->_getAbsolutePath($trailingPath);
            if (empty($absPath)) {
                return false;
            }
            $fdlPath = $this->modx->getObject('fdPaths', array(
                'ctx' => $this->modx->context->key,
                'media_source_id' => $this->config['mediaSourceId'],
                'filename' => $absPath,
            ));
            if (!$fdlPath) {
                $cdb = array();
                $cdb['ctx'] = $this->modx->context->key;
                $cdb['filename'] = $absPath;

                $checkedDb = $this->_checkDb($cdb, false);
                if (!$checkedDb) {
                    continue;
                }
                $fdlPath = $this->modx->getObject('fdPaths', array(
                    'ctx' => $this->modx->context->key,
                    'media_source_id' => $this->config['mediaSourceId'],
                    'filename' => $absPath
                ));
            }
            $hash = $fdlPath->get('hash');
            $link = $this->_linkDirOpen($hash, $this->modx->context->key);

            if ($k === 0) {
                $pageUrl = $this->modx->makeUrl($this->modx->resource->get('id'));
                $trail[$k] = array(
                    $this->config['prefix'] . 'title' => $this->modx->lexicon($this->config['prefix'] . 'breadcrumb.home'),
                    $this->config['prefix'] . 'link' => $pageUrl,
                    $this->config['prefix'] . 'url' => $pageUrl,
                    $this->config['prefix'] . 'hash' => '',
                );
            } else {
                $trail[$k] = array(
                    $this->config['prefix'] . 'title' => $title,
                    $this->config['prefix'] . 'link' => $link['url'], // fallback
                    $this->config['prefix'] . 'url' => $link['url'],
                    $this->config['prefix'] . 'hash' => $hash,
                );
            }
            if ($k < ($countTrimmedPathX - 1)) {
                $trailingLink[] = $this->parseTpl($this->config['tplBreadcrumb'], $trail[$k]);
            } else {
                $trailingLink[] = $title;
            }
        }
        $breadcrumb = @implode($this->config['breadcrumbSeparator'], $trailingLink);

        return $breadcrumb;
    }

    public function parseTemplate() {
        $o = $this->_tplWrapper();
        return $o;
    }

    /**
     * Sets the salted parameter to the database
     * @param   string  $ctx        context
     * @param   string  $filename   filename
     * @return  string  hashed parameter
     */
    private function _setHashedParam($ctx, $filename) {
        $input = $this->config['saltText'] . $ctx . $this->config['mediaSourceId'] . $filename;
        return str_rot13(base64_encode(hash('sha512', $input)));
    }

    /**
     * Gets the salted parameter from the System Settings + stored hashed parameter.
     * @param   string  $ctx        context
     * @param   string  $filename   filename
     * @return  string  hashed parameter
     */
    private function _getHashedParam($ctx, $filename) {
        if (!empty($this->mediaSource)) {
            $search = $this->getBasePath($filename);
            if (!empty($search)) {
                $filename = str_replace($search, '', $filename);
            }
        }
        $fdlPath = $this->modx->getObject('fdPaths', array(
            'ctx' => $ctx,
            'media_source_id' => $this->config['mediaSourceId'],
            'filename' => $filename
        ));
        if (!$fdlPath) {
            return false;
        }
        return $fdlPath->get('hash');
    }

    /**
     * Check whether the REQUEST parameter exists in the database.
     * @param   string  $ctx    context
     * @param   string  $hash   hash value
     * @return  bool    true | false
     */
    public function checkHash($ctx, $hash) {
        $fdlPath = $this->modx->getObject('fdPaths', array(
            'ctx' => $ctx,
            'hash' => $hash
        ));
        if (!$fdlPath) {
            return false;
        }
        return true;
    }

    /**
     * Get applied plugins and set custom properties by event's provider
     * @param type $eventName
     * @param type $customProperties
     * @param type $toString
     * @return type
     */
    public function getPlugins($eventName, $customProperties = array(), $toString = false) {
        if (empty($this->plugins)) {
            return;
        }
        if (!is_array($customProperties)) {
            $customProperties = array();
        }

        $this->plugins->setProperties($customProperties);
        return $this->plugins->getPlugins($eventName, $toString);
    }

}
