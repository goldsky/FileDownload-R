<?php

/**
 * The main class for the FileDownload snippet for MODX Revolution
 * This is the conversion of the original FileDownload snippet for MODX
 * Evolution, which was originally created by Kyle Jaebker.
 * The main parameters are taken from that version so any conversion can be done
 * smoothly.
 *
 * @author goldsky <goldsky@fastmail.fm>
 * @package main class
 */
class FileDownload {

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
     * @var string
     */
    private $_error = '';
    /**
     * To hold output message
     * @var string
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
     * constructor
     * @param   modX    $modx
     * @param   array   $config    parameters
     */
    public function __construct(modX $modx, $config = array()) {
        $this->modx = &$modx;

        $config['getDir'] = !empty($config['getDir']) ? $this->_checkPath($config['getDir']) : '';
        $config['origDir'] = !empty($config['getDir']) ? $config['getDir'] : ''; // getDir will be overridden by setDirProp()
        $config['getFile'] = !empty($config['getFile']) ? $this->_checkPath($config['getFile']) : '';
        $config = $this->replacePropPhs($config);

        $corePath = $this->modx->getOption('core_path');
        $basePath = $corePath . 'components/filedownloadr/';
        $assetsUrl = $this->modx->getOption('assets_url') . 'components/filedownloadr/';

        $this->_output = array(
            'rows' => '',
            'dirRows' => '',
            'fileRows' => ''
        );
        $this->config = array_merge(array(
            'corePath' => $corePath,
            'basePath' => $basePath,
            'modelPath' => $basePath . 'models/',
            'processorsPath' => $basePath . 'processors/',
            'controllersPath' => $basePath . 'controllers/',
            'chunksPath' => $basePath . 'elements/chunks/',
            'jsUrl' => $assetsUrl . 'js/',
            'cssUrl' => $assetsUrl . 'css/',
            'imgTypeUrl' => $assetsUrl . 'img/filetypes/',
            'imgLocat' => $assetsUrl . 'img/filetypes/',
            'assetsUrl' => $assetsUrl,
            'encoding' => 'utf-8'
                ), $config);

        $this->modx->addPackage('filedownload', $this->config['modelPath']);

        if (!$this->modx->lexicon) {
            $this->modx->getService('lexicon', 'modLexicon');
        }
        $this->modx->lexicon->load('filedownloadr:default');

        $this->_imgType = $this->_imgTypeProp();
        if (!empty($this->config['encoding']))
            mb_internal_encoding($this->config['encoding']);

        if (!empty($this->config['plugins'])) {
            if (!$this->modx->loadClass('filedownload.FileDownloadPlugin', $this->config['modelPath'], true, true)) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, '[FileDownload] could not load plugin class.');
                return false;
            }
            $this->plugins = new FileDownloadPlugin($this);
        }
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
        $config['origDir'] = !empty($config['getDir']) ? $config['getDir'] : ''; // getDir will be overridden by setDirProp()
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
        $this->_error = $msg;
    }

    /**
     * Get string error for boolean returned methods
     * @return  string  output
     */
    public function getError() {
        return $this->_error;
    }

    /**
     * Set string output for boolean returned methods
     * @return  void
     */
    public function setOutput($msg) {
        $this->_output = $msg;
    }

    /**
     * Get string output for boolean returned methods
     * @return  string  output
     */
    public function getOutput() {
        return $this->_output;
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
     * @return  mixed   boolean|array of placeholders
     */
    public function setPlaceholders($placeholders, $prefix = '') {
        if (empty($placeholders)) {
            return FALSE;
        }
        $prefix = !empty($prefix) ? $prefix : (isset($this->config['phsPrefix']) ? $this->config['phsPrefix'] : '');
        $placeholders = $this->trimArray($placeholders);
        $placeholders = $this->implodePhs($placeholders, rtrim($prefix, '.'));
        // enclosed private scope
        $this->_placeholders = array_merge($this->_placeholders, $placeholders);
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
        if (preg_match('/^(@CODE|@INLINE)/i', $tpl)) {
            $tplString = preg_replace('/^(@CODE|@INLINE)/i', '', $tpl);
            // tricks @CODE: / @INLINE:
            $tplString = ltrim($tplString, ':');
            $tplString = trim($tplString);
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
        $chunk = $this->modx->newObject('modChunk');

        // just to create a name for the modChunk object.
        $name = strtolower(basename($file));
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
                $realpath = realpath($path);
                if (empty($realpath)) {
                    $realpath = realpath(MODX_BASE_PATH . $path);
                    if (empty($realpath)) {
                        continue;
                    }
                }
                if (in_array($realpath, $forbiddenFolders)) {
                    continue;
                }
                $cleanPaths[] = $realpath;
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
        if ($plugins === FALSE) { // strict detection
            return FALSE;
        }

        $dirContents = array();
        if (!empty($this->config['getDir'])) {
            $dirContents = $this->_getDirContents($this->config['getDir']);
            if (!$dirContents)
                $dirContents = array();
        }
        $fileContents = array();
        if (!empty($this->config['getFile'])) {
            $fileContents = $this->_getFileContents($this->config['getFile']);
            if (!$fileContents)
                $fileContents = array();
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
     * Check the called file contents with the registered database.
     * If it's not listed, auto save
     * @param   array   $file   Realpath filename / dirname
     * @return  void
     */
    private function _checkDb(array $file) {
        if (empty($file)) {
            return FALSE;
        }

        $realPath = realpath($file['filename']);
        if (empty($realPath)) {
            return FALSE;
        }

        $fdlObj = $this->modx->getObject('FDL', array(
            'ctx' => $file['ctx'],
            'filename' => utf8_encode($file['filename'])
        ));
        $checked = array();
        if ($fdlObj === null) {
            $fdlObj = $this->modx->newObject('FDL');
            $fdlObj->fromArray(array(
                'ctx' => $file['ctx'],
                'filename' => utf8_encode($file['filename']),
                'count' => 0,
                'hash' => $this->_setHashedParam($file['ctx'], $file['filename'])
            ));
            $fdlObj->save();
            $checked['ctx'] = $fdlObj->get('ctx');
            $checked['filename'] = $fdlObj->get('filename');
            $checked['count'] = $fdlObj->get('count');
            $checked['hash'] = $fdlObj->get('hash');
            return $checked;
        } else {
            $checked['ctx'] = $fdlObj->get('ctx');
            $checked['filename'] = $fdlObj->get('filename');
            $checked['count'] = $fdlObj->get('count');
            $checked['hash'] = $fdlObj->get('hash');
            return $checked;
        }

        return FALSE;
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
                $this->_count['dirs']++;
            } else {
                $this->_count['files']++;
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
     * Load UTF-8 Class
     * @param   string  $callback       method's name
     * @param   array   $callbackParams call back parameters (in an array)
     * @author  Rin
     * @link    http://forum.dklab.ru/viewtopic.php?p=91015#91015
     * @return  string  converted text
     */
    private function _utfRin($callback, array $callbackParams = array()) {
        include_once(dirname(dirname(dirname(__FILE__))) . '/includes/UTF8-2.1.1/UTF8.php');
        include_once(dirname(dirname(dirname(__FILE__))) . '/includes/UTF8-2.1.1/ReflectionTypehint.php');

        $utf = call_user_func_array(array('UTF8', $callback), $callbackParams);
        return $utf;
    }

    /**
     * Retrieve the content of the given directory path
     * @param   array   $paths      The specified root path
     * @return  array   Dir's contents in an array
     */
    private function _getDirContents(array $paths = array()) {
        if (empty($paths)) {
            return FALSE;
        }

        $contents = array();
        foreach ($paths as $rootPath) {
            if (!is_dir($rootPath)) {
                // @todo: lexicon
                $this->modx->log(
                        modX::LOG_LEVEL_ERROR, '&getDir parameter expects a correct dir path. <b>"' . $rootPath . '"</b> is given.'
                );
                return FALSE;
            }

            $plugins = $this->getPlugins('BeforeDirOpen', array(
                'dirPath' => $rootPath,
            ));

            if ($plugins === FALSE) { // strict detection
                return FALSE;
            } elseif ($plugins === 'continue') {
                continue;
            }

            $scanDir = scandir($rootPath);

            foreach ($scanDir as $file) {
                if ($file === '.' || $file === '..' || $file === 'Thumbs.db' || $file === '.htaccess' || $file === '.htpasswd' || $file === '.ftpquota' || $file === '.DS_Store'
                ) {
                    continue;
                }

                $rootRealPath = realpath($rootPath);
                if (!$rootRealPath) {
                    return FALSE;
                }

                $fullPath = $rootRealPath . DIRECTORY_SEPARATOR . $file;
                $fileType = @filetype($fullPath);

                if ($fileType == 'file') {
                    $fileInfo = $this->_fileInformation($fullPath);
                    if (!$fileInfo) {
                        continue;
                    }
                    $contents[] = $fileInfo;
                } elseif ($this->config['browseDirectories']) {
                    // a directory
                    $cdb['ctx'] = $this->modx->context->key;
                    $cdb['filename'] = $fullPath;
                    $cdb['count'] = $this->_getDownloadCount($cdb['ctx'], $cdb['filename']);
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
                        'fullPath' => utf8_encode($fullPath),
                        'path' => utf8_encode($rootRealPath),
                        'filename' => utf8_encode($file),
                        'alias' => utf8_encode($alias),
                        'type' => $fileType,
                        'ext' => '',
                        'size' => '',
                        'sizeText' => '',
                        'unixdate' => $unixDate,
                        'date' => $date,
                        'image' => $this->config['imgTypeUrl'] . $imgType,
                        'count' => $checkedDb['count'],
                        'link' => $link['url'], // fallback
                        'url' => $link['url'],
                        'hash' => $checkedDb['hash']
                    );

                    $contents[] = $dir;
                }
            }

            $plugins = $this->getPlugins('AfterDirOpen', array(
                'dirPath' => $rootPath,
                'contents' => $contents,
            ));

            if ($plugins === FALSE) { // strict detection
                return FALSE;
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
        $fileRealPath = realpath($path);
        if (!is_file($fileRealPath) || !$fileRealPath) {
            // @todo: lexicon
            $this->modx->log(
                    modX::LOG_LEVEL_ERROR, '&getFile parameter expects a correct file path. ' . $path . ' is given.'
            );
            return FALSE;
        }

        $baseName = basename($fileRealPath);
        $xBaseName = explode('.', $baseName);
        $tempExt = end($xBaseName);
        $ext = strtolower($tempExt);
        $size = filesize($fileRealPath);
        $imgType = $this->_imgType($ext);

        if (!$this->_isExtShown($ext)) {
            return FALSE;
        }
        if ($this->_isExtHidden($ext)) {
            return FALSE;
        }

        $cdb['ctx'] = $this->modx->context->key;
        $cdb['filename'] = $fileRealPath;
        $cdb['count'] = $this->_getDownloadCount($cdb['ctx'], $cdb['filename']);
        $cdb['hash'] = $this->_getHashedParam($cdb['ctx'], $cdb['filename']);

        $checkedDb = $this->_checkDb($cdb);
        if (!$checkedDb) {
            return FALSE;
        }

        if ($this->config['directLink']) {
            $link = $this->_directLinkFileDownload(utf8_decode($checkedDb['filename']));
            if (!$link)
                return FALSE;
        } else {
            $link = $this->_linkFileDownload($checkedDb['filename'], $checkedDb['hash'], $checkedDb['ctx']);
        }

        $unixDate = filemtime($fileRealPath);
        $date = date($this->config['dateFormat'], $unixDate);
        $info = array(
            'ctx' => $checkedDb['ctx'],
            'fullPath' => $fileRealPath,
            'path' => utf8_encode(dirname($fileRealPath)),
            'filename' => utf8_encode($baseName),
            'alias' => $alias,
            'type' => filetype($fileRealPath),
            'ext' => $ext,
            'size' => $size,
            'sizeText' => $this->_fileSizeText($size),
            'unixdate' => $unixDate,
            'date' => $date,
            'image' => $this->config['imgTypeUrl'] . $imgType,
            'count' => $checkedDb['count'],
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
     * Get the right image type to the specified file's extension, or fall back
     * to the default image.
     * @param string $ext
     * @return type
     */
    private function _imgType($ext) {
        return isset($this->_imgType[$ext]) ? $this->_imgType[$ext] : (isset($this->_imgType['default']) ? $this->_imgType['default'] : FALSE);
    }

    /**
     * Retrieve the images for the specified file extensions
     * @return  array   file type's images
     */
    private function _imgTypeProp() {
        if (empty($this->config['imgLocat'])) {
            return FALSE;
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
            $args = 'fdlfile=' . $hash;
            $url = $this->modx->makeUrl($this->modx->resource->get('id'), $ctx, $args);
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
        if ($this->config['noDownload']) {
            $link['url'] = $filePath;
        } else {
            // to use this method, the file should always be placed on the web root
            $corePath = str_replace('/', DIRECTORY_SEPARATOR, MODX_CORE_PATH);
            if (stristr($filePath, $corePath)) {
                return FALSE;
            }
            // switching from absolute path to url is nuts
            $fileUrl = str_ireplace(MODX_BASE_PATH, MODX_SITE_URL, $filePath);
            $fileUrl = str_replace(DIRECTORY_SEPARATOR, '/', $fileUrl);
            $parseUrl = parse_url($fileUrl);
            $url = ltrim($parseUrl['path'], '/' . MODX_HTTP_HOST);
            $link['url'] = MODX_URL_SCHEME . MODX_HTTP_HOST . '/' . $url;
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
            return FALSE;
        }
        $link = array();
        $args = 'fdldir=' . $hash;
        if (!empty($this->config['fdlid'])) {
            $args .= '&fdlid=' . $this->config['fdlid'];
        }
        $url = $this->modx->makeUrl($this->modx->resource->get('id'), $ctx, $args);
        $link['url'] = $url;
        $link['hash'] = $hash;
        return $link;
    }

    /**
     * Set the new value to the getDir property to browse inside the clicked
     * directory
     * @param   string  $hash       the hashed link
     * @param   bool    $selected   to patch multiple snippet call
     * @return  bool    TRUE | FALSE
     */
    public function setDirProp($hash, $selected = true) {
        if (empty($hash) || !$selected) {
            return FALSE;
        }
        $fdlObj = $this->modx->getObject('FDL', array('hash' => $hash));
        if (!$fdlObj) {
            return FALSE;
        }

        $ctx = $fdlObj->get('ctx');
        $path = $fdlObj->get('filename');
        $count = $fdlObj->get('count');

        if ($this->modx->context->key !== $ctx) {
            return FALSE;
        }

        $this->config['getDir'] = array($path);
        $this->config['getFile'] = array();

        // save the new count
        $newCount = $count + 1;
        $fdlObj->set('count', $newCount);
        if ($fdlObj->save() === false) {
            // @todo setDirProp: lexicon string
            return $this->modx->error->failure($this->modx->lexicon($this->config['prefix'] . 'err_save_counter'));
        }

        return TRUE;
    }

    /**
     * Download action
     * @param   string  $hash   hashed text
     * @return  void    file is pulled to the browser
     */
    public function downloadFile($hash) {
        if (empty($hash)) {
            return FALSE;
        }

        $fdlObj = $this->modx->getObject('FDL', array('hash' => $hash));
        if (!$fdlObj) {
            return FALSE;
        }

        $ctx = $fdlObj->get('ctx');
        $filePath = utf8_decode($fdlObj->get('filename'));
        $count = $fdlObj->get('count');

        if ($this->modx->context->key !== $ctx) {
            return FALSE;
        }

        $plugins = $this->getPlugins('BeforeFileDownload', array(
            'hash' => $hash,
            'ctx' => $ctx,
            'filePath' => $filePath,
            'count' => $count,
        ));

        if ($plugins === FALSE) { // strict detection
            return FALSE;
        }

        if (file_exists($filePath)) {
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
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
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

            if ($this->config['countDownloads']) {
                // save the new count
                $newCount = $count + 1;
                $fdlObj->set('count', $newCount);
                if ($fdlObj->save() === false) {
                    // @todo downloadFile: lexicon string
                    return $this->modx->error->failure($this->modx->lexicon('filedownload.fdl_err_save'));
                }
            }

            // just run this away, it doesn't matter if the return is FALSE
            $this->getPlugins('AfterFileDownload', array(
                'hash' => $hash,
                'ctx' => $ctx,
                'filePath' => $filePath,
                'count' => $newCount,
            ));

            exit;
        }

        return FALSE;
    }

    /**
     * Get the download counting for the specified file and context
     * @param type $ctx
     * @param type $filePath
     * @return type
     */
    private function _getDownloadCount($ctx, $filePath) {
        $fdlObj = $this->modx->getObject('FDL', array(
            'ctx' => $ctx,
            'filename' => $filePath
        ));
        if (!$fdlObj)
            return '';

        return $fdlObj->get('count');
    }

    /**
     * Check whether the file with the specified extension is hidden from the list
     * @param   string  $ext    file's extension
     * @return  bool    TRUE | FALSE
     */
    private function _isExtHidden($ext) {
        if (empty($this->config['extHidden'])) {
            return FALSE;
        }
        $extHiddenX = @explode(',', $this->config['extHidden']);
        array_walk($extHiddenX, create_function('&$val', '$val = strtolower(trim($val));'));
        if (!in_array($ext, $extHiddenX)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Check whether the file with the specified extension is shown to the list
     * @param   string  $ext    file's extension
     * @return  bool    TRUE | FALSE
     */
    private function _isExtShown($ext) {
        if (empty($this->config['extShown'])) {
            return TRUE;
        }
        $extShownX = @explode(',', $this->config['extShown']);
        array_walk($extShownX, create_function('&$val', '$val = strtolower(trim($val));'));
        if (in_array($ext, $extShownX)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Check the user's group
     * @param   void
     * @return  bool    TRUE | FALSE
     */
    public function isAllowed() {
        if (empty($this->config['userGroups'])) {
            return TRUE;
        } else {
            $userGroupsX = @explode(',', $this->config['userGroups']);
            array_walk($userGroupsX, create_function('&$val', '$val = trim($val);'));
            $userAccessGroupNames = $this->_userAccessGroupNames();

            $intersect = array_uintersect($userGroupsX, $userAccessGroupNames, "strcasecmp");

            if (count($intersect) > 0) {
                return TRUE;
            } else {
                return FALSE;
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
            return FALSE;
        }

        $sortType = array();
        foreach ($contents as $k => $file) {
            if (empty($this->config['browseDirectories']) && $file['type'] === 'dir') {
                continue;
            }
            $sortType[$file['type']][$k] = $file;
        }
        if (empty($sortType)) {
            return FALSE;
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
     * @param   bool    $natSort        TRUE | FALSE
     * @param   bool    $caseSensitive  TRUE | FALSE
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
                $temp = array_reverse($temp, TRUE);
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
        $groupPath = str_replace(DIRECTORY_SEPARATOR, $this->config['breadcrumbSeparator'], $this->_trimPath($path));
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
        $xPath = @explode(DIRECTORY_SEPARATOR, $this->config['origDir'][0]);
        array_pop($xPath);
        $parentPath = @implode(DIRECTORY_SEPARATOR, $xPath) . DIRECTORY_SEPARATOR;
        $trimmedPath = $path;
        if (FALSE !== stristr($trimmedPath, $parentPath)) {
            $trimmedPath = str_replace($parentPath, '', $trimmedPath);
        }

        $modxCorePath = realpath(MODX_CORE_PATH) . DIRECTORY_SEPARATOR;
        $modxAssetsPath = realpath(MODX_ASSETS_PATH) . DIRECTORY_SEPARATOR;
        if (FALSE !== stristr($trimmedPath, $modxCorePath)) {
            $trimmedPath = str_replace($modxCorePath, '', $trimmedPath);
        } elseif (FALSE !== stristr($trimmedPath, $modxAssetsPath)) {
            $trimmedPath = str_replace($modxAssetsPath, '', $trimmedPath);
        }

        return $trimmedPath;
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

        $trimmedPath = trim($this->_trimPath($path), DIRECTORY_SEPARATOR);

        $basePath = str_replace($trimmedPath, '', $path);
        $trimmedPathX = @explode(DIRECTORY_SEPARATOR, $trimmedPath);

        $trailingPath = $basePath;
        $trail = array();
        $trailingLink = array();
        $countTrimmedPathX = count($trimmedPathX);
        foreach ($trimmedPathX as $k => $title) {
            $trailingPath .= $title . DIRECTORY_SEPARATOR;
            $fdlObj = $this->modx->getObject('FDL', array(
                'filename' => $trailingPath
            ));
            if (!$fdlObj) {
                $cdb = array();
                $cdb['ctx'] = $this->modx->context->key;
                $cdb['filename'] = $trailingPath;

                $checkedDb = $this->_checkDb($cdb);
                if (!$checkedDb) {
                    continue;
                }
                $fdlObj = $this->modx->getObject('FDL', array(
                    'filename' => $trailingPath
                ));
            }
            $hash = $fdlObj->get('hash');
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
        $input = $this->config['saltText'] . $ctx . $filename;
        return str_rot13(base64_encode(hash('sha512', $input)));
    }

    /**
     * Gets the salted parameter from the System Settings + stored hashed parameter.
     * @param   string  $ctx        context
     * @param   string  $filename   filename
     * @return  string  hashed parameter
     */
    private function _getHashedParam($ctx, $filename) {
        $fdlObj = $this->modx->getObject('FDL', array(
            'ctx' => $ctx,
            'filename' => $filename
        ));
        if (!$fdlObj) {
            return FALSE;
        }
        return $fdlObj->get('hash');
    }

    /**
     * Check whether the REQUEST parameter exists in the database.
     * @param   string  $ctx    context
     * @param   string  $hash   hash value
     * @return  bool    TRUE | FALSE
     */
    public function checkHash($ctx, $hash) {
        $fdlObj = $this->modx->getObject('FDL', array(
            'ctx' => $ctx,
            'hash' => $hash
        ));
        if (!$fdlObj) {
            return FALSE;
        }
        return TRUE;
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
        if (!is_array($customProperties))
            $customProperties = array();

        $this->plugins->setProperties($customProperties);
        return $this->plugins->getPlugins($eventName, $toString);
    }

}
