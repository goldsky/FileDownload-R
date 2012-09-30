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

    public $modx;
    public $configs = array();
    private $_template = array();
    private $_count = array();
    private $_imgType = array();

    public function __construct(modX &$modx) {
        $this->modx = &$modx;
    }

    public function setConfigs($configs = array()) {
        $configs['getDir'] = $this->_checkPath($configs['getDir']);
        $configs['origDir'] = $configs['getDir']; // getDir will be overridden by setDirProp()
        $configs['getFile'] = $this->_checkPath($configs['getFile']);

        $configs = $this->replacePropPhs($configs);
        $corePath = $this->modx->getOption('core_path');
        $basePath = $corePath . 'components/filedownload/';
        $assetsUrl = $this->modx->getOption('assets_url') . 'components/filedownload/';
        $this->configs = array();
        $this->_template = array();
        $this->configs = array_merge(array(
            'corePath' => $corePath,
            'basePath' => $basePath,
            'modelPath' => $basePath . 'models/',
            'processorsPath' => $basePath . 'processors/',
            'controllersPath' => $basePath . 'controllers/',
            'chunksPath' => $basePath . 'elements/chunks/',
            'jsUrl' => $assetsUrl . 'js/',
            'cssUrl' => $assetsUrl . 'css/',
            'imgTypeUrl' => $assetsUrl . 'img/filetypes/',
            'assetsUrl' => $assetsUrl,
            'encoding' => 'utf-8'
                ), $configs);

        $this->modx->addPackage('filedownload', $this->configs['modelPath']);

        $this->modx->getService('lexicon', 'modLexicon');
        if ($this->modx->lexicon) {
            $this->modx->lexicon->load('filedownload:default');
        }

        $this->_imgType = $this->_imgTypeProp();
        mb_internal_encoding($this->configs['encoding']);
    }

    public function getConfig($key) {
        return $this->configs[$key];
    }

    public function getConfigs() {
        return $this->configs;
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
     * Trim array values
     * @param   array   $array      array contents
     * @param   string  $charlist   [default: null] defined characters to be trimmed
     * @link    http://php.net/manual/en/function.trim.php
     * @return  array   trimmed array
     */
    public function trimArray(array $array, $charlist = null) {
        $newArray = array();
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $array[$k][$v] = $this->trimArray($v);
            } else {
                $val = $this->trimString($v, $charlist);
                if (empty($v)) {
                    continue;
                }
                $newArray[$k] = $val;
            }
        }
        sort($newArray);

        return $newArray;
    }

    /**
     * Trim string value
     * @param   string  $string     source text
     * @param   string  $charlist   defined characters to be trimmed
     * @link    http://php.net/manual/en/function.trim.php
     * @return  string  trimmed text
     */
    public function trimString($string, $charlist = null) {
        $string = htmlentities($string);
        // blame TinyMCE!
        $string = preg_replace('/(&Acirc;|&nbsp;)+/i', '', $string);
        if ($charlist === null) {
            $string = trim($string);
        } else {
            $string = trim($string, $charlist);
        }

        if (empty($string)) {
            return FALSE;
        }

        return $string;
    }

    /**
     * Retrieve the content of the given path
     * @param   mixed   $root   The specified root path
     * @return  array   All contents in an array
     */
    public function getContents() {
        $plugins = $this->getPlugins('OnLoad', $this->configs);
        if ($plugins === FALSE) { // strict detection
            return FALSE;
        }

        $dirContents = array();
        if (!empty($this->configs['getDir'])) {
            $dirContents = $this->_getDirContents($this->configs['getDir']);
            if (!$dirContents)
                $dirContents = array();
        }
        $fileContents = array();
        if (!empty($this->configs['getFile'])) {
            $fileContents = $this->_getFileContents($this->configs['getFile']);
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

        if (empty($this->configs['chkDesc'])) {
            foreach ($contents as $key => $file) {
                $contents[$key]['description'] = '';
            }
            return $contents;
        }

        $chunkContent = $this->modx->getChunk($this->configs['chkDesc']);

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
        if (empty($file) || !realpath($file['filename'])) {
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
                if ($file === '.'
                        || $file === '..'
                        || $file === 'Thumbs.db'
                        || $file === '.htaccess'
                        || $file === '.htpasswd'
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
                } elseif ($this->configs['browseDirectories']) {
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

                    $date = date($this->configs['dateFormat'], filemtime($fullPath));
                    $link = $this->_linkDirOpen($checkedDb['hash'], $checkedDb['ctx']);

                    $imgType = $this->_imgType('dir');
                    $dir = array(
                        'ctx' => $checkedDb['ctx'],
                        'fullPath' => $fullPath,
                        'path' => $rootRealPath,
                        'filename' => $file,
                        'alias' => $alias,
                        'type' => $fileType,
                        'ext' => '',
                        'size' => '',
                        'sizeText' => '',
                        'date' => $date,
                        'image' => $this->configs['imgTypeUrl'] . $imgType,
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
        $ext = strtolower(end(explode('.', $baseName)));
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

        if ($this->configs['directLink']) {
            $link = $this->_directLinkFileDownload(utf8_decode($checkedDb['filename']));
            if (!$link)
                return FALSE;
        } else {
            $link = $this->_linkFileDownload($checkedDb['filename'], $checkedDb['hash'], $checkedDb['ctx']);
        }

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
            'date' => date($this->configs['dateFormat'], filemtime($fileRealPath)),
            'image' => $this->configs['imgTypeUrl'] . $imgType,
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
     * Parsing template
     * @param   string  $tpl    @BINDINGs options, code/file/chunk/no @binding to chunk
     * @param   array   $phs    placeholders
     * @return  string  parsed output
     */
    public function parseTpl($tpl, array $phs) {
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
            $tpl = preg_replace('/^@CHUNK/i', '', $tpl);
            // tricks @CHUNK:
            $tpl = ltrim($tpl, ':');
            $tpl = trim($tpl);

            $chunk = $this->modx->getObject('modChunk', array('name' => $tpl), true);
            if (empty($chunk)) {
                // try to use @splittingred's fallback
                $f = $this->configs['chunksPath'] . strtolower($tpl) . '.chunk.tpl';
                try {
                    $output = $this->parseTplFile($f, $phs);
                } catch (Exception $e) {
                    $output = $e->getMessage();
                    return 'Chunk: ' . $tpl . ' is not found, neither the file ' . $output;
                }
            } else {
                $output = $this->modx->getChunk($tpl, $phs);
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
    public function parseTplCode($code, $phs) {
        $chunk = $this->modx->newObject('modChunk');
        $chunk->setContent($code);
        $chunk->setCacheable(false);
        $phs = $this->replacePropPhs($phs);
        return $chunk->process($phs);
    }

    /**
     * Parsing file based template
     * @param   string  $file   file path
     * @param   array   $phs    placeholders
     * @return  string  parsed output
     * @throws Exception if file is not found
     */
    public function parseTplFile($file, $phs) {
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
        $output = $chunk->process($phs);

        return $output;
    }

    /**
     * Get the right image type to the specified file's extension, or fall back
     * to the default image.
     * @param string $ext
     * @return type
     */
    private function _imgType($ext) {
        $imgType = $this->_imgType[$ext];
        if (!$imgType)
            $ext = 'default';
        return $this->_imgType[$ext];
    }

    /**
     * Retrieve the images for the specified file extensions
     * @return  array   file type's images
     */
    private function _imgTypeProp() {
        if (empty($this->configs['imgLocat'])) {
            return FALSE;
        }
        $fdImagesChunk = $this->modx->getChunk($this->configs['imgTypes']);
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
        if ($this->configs['noDownload']) {
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
        if ($this->configs['noDownload']) {
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
        if (!$this->configs['browseDirectories']) {
            return FALSE;
        }
        $link = array();
        $args = 'fdldir=' . $hash;
        if (!empty($this->configs['fdlid'])) {
            $args .= '&fdlid=' . $this->configs['fdlid'];
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

        $this->configs['getDir'] = array($path);
        $this->configs['getFile'] = array();

        // save the new count
        $newCount = $count + 1;
        $fdlObj->set('count', $newCount);
        if ($fdlObj->save() === false) {
            // @todo setDirProp: lexicon string
            return $this->modx->error->failure($this->modx->lexicon('fd.err_save_counter'));
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

            if ($this->configs['countDownloads']) {
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
        if (empty($this->configs['extHidden'])) {
            return FALSE;
        }
        $extHiddenX = @explode(',', $this->configs['extHidden']);
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
        if (empty($this->configs['extShown'])) {
            return TRUE;
        }
        $extShownX = @explode(',', $this->configs['extShown']);
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
        if (empty($this->configs['userGroups'])) {
            return TRUE;
        } else {
            $userGroupsX = @explode(',', $this->configs['userGroups']);
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

        if (!$this->configs['groupByDirectory']) {
            $sort = $this->_groupByType($contents);
        } else {
            $sortPath = array();
            foreach ($contents as $k => $file) {
                if (!$this->configs['browseDirectories'] && $file['type'] === 'dir') {
                    continue;
                }
                $sortPath[$file['path']][$k] = $file;
            }

            $this->_template['wrapper'] = !empty($this->_template['wrapper']) ? $this->_template['wrapper'] : '';
            $sort = array();
            foreach ($sortPath as $k => $path) {
                // path name for the &groupByDirectory template: tpl-group
                $this->_template['wrapper'] .= $this->_tplDirectory($k);

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
            if (!$this->configs['browseDirectories'] && $file['type'] === 'dir') {
                continue;
            }
            $sortType[$file['type']][$k] = $file;
        }
        if (empty($sortType)) {
            return FALSE;
        }
        foreach ($sortType as $k => $file) {
            $sortType[$k] = $this->_sortMultiOrders(
                    $file
                    , $this->configs['sortBy']
                    , $this->configs['sortOrder']
                    , $this->configs['sortOrderNatural']
                    , $this->configs['sortByCaseSensitive']
            );
        }

        $sort = array();
        $tplWrapper = !empty($this->_template['wrapper']) ? $this->_template['wrapper'] : '';
        $dirs = '';
        if ($this->configs['browseDirectories'] && !empty($sortType['dir'])) {
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
        $phs['fd.classPath'] = (!empty($this->configs['cssPath'])) ? ' class="' . $this->configs['cssPath'] . '"' : '';
        $phs['fd.path'] = $this->_breadcrumbs();

        if (!empty($this->configs['tplWrapperDir']) && !empty($dirs)) {
            $phs['fd.dirRows'] = $dirs;
            $tplWrapper .= $this->parseTpl($this->configs['tplWrapperDir'], $phs);
        } else {
            $tplWrapper .= $dirs;
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

        if (!empty($this->configs['tplWrapperFile']) && !empty($files)) {
            $phs['fd.fileRows'] = $files;
            $tplWrapper .= $this->parseTpl($this->configs['tplWrapperFile'], $phs);
        } else {
            $tplWrapper .= $files;
        }

        $this->_template['wrapper'] = $tplWrapper;

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
    private function _sortMultiOrders($array, $index, $order, $natSort = FALSE, $caseSensitive = FALSE) {
        if (!is_array($array) || count($array) < 1) {
            return $array;
        }

        $temp = array();
        foreach (array_keys($array) as $key) {
            $temp[$key] = $array[$key][$index];
        }

        if (!$natSort) {
            if (strtolower($order) == 'asc') {
                asort($temp);
            } else {
                arsort($temp);
            }
        } else {
            if (!$caseSensitive) {
                natcasesort($temp);
            } else {
                natsort($temp);
            }
            if (strtolower($order) != 'asc') {
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
        if (!empty($this->configs['cssDir'])) {
            $cssName[] = $this->configs['cssDir'];
        }
        if (!empty($this->configs['cssAltRow']) && $row % 2 === 1) {
            $cssName[] = $this->configs['cssAltRow'];
        }
        if (!empty($this->configs['cssFirstDir']) && $row === 1) {
            $cssName[] = $this->configs['cssFirstDir'];
        } elseif (!empty($this->configs['cssLastDir']) && $row === $totalRow) {
            $cssName[] = $this->configs['cssLastDir'];
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
        if (!empty($this->configs['cssFile'])) {
            $cssName[] = $this->configs['cssFile'];
        }
        if (!empty($this->configs['cssAltRow']) && $row % 2 === 1) {
            if ($this->_count['dirs'] % 2 === 0) {
                $cssName[] = $this->configs['cssAltRow'];
            }
        }
        if (!empty($this->configs['cssFirstFile']) && $row === 1) {
            $cssName[] = $this->configs['cssFirstFile'];
        } elseif (!empty($this->configs['cssLastFile']) && $row === $totalRow) {
            $cssName[] = $this->configs['cssLastFile'];
        }
        if ($this->configs['cssExtension']) {
            $cssNameExt = '';
            if (!empty($this->configs['cssExtensionPrefix'])) {
                $cssNameExt .= $this->configs['cssExtensionPrefix'];
            }
            $cssNameExt .= $ext;
            if (!empty($this->configs['cssExtensionSuffix'])) {
                $cssNameExt .= $this->configs['cssExtensionSuffix'];
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
            $phs['fd.' . $k] = $v;
        }
        $tpl = $this->parseTpl($this->configs['tplDir'], $phs);

        return $tpl;
    }

    /**
     * Parsing the file template
     * @param   array   $fileInfo   properties
     * @return  string  rendered HTML
     */
    private function _tplFile(array $fileInfo) {
        if (empty($fileInfo)) {
            return '';
        }
        foreach ($fileInfo as $k => $v) {
            $phs['fd.' . $k] = $v;
        }
        $tpl = $this->parseTpl($this->configs['tplFile'], $phs);

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
        $phs['fd.class'] = (!empty($this->configs['cssGroupDir'])) ? ' class="' . $this->configs['cssGroupDir'] . '"' : '';
        $phs['fd.groupDirectory'] = $this->_trimPath($path);
        $tpl = $this->parseTpl($this->configs['tplGroupDir'], $phs);

        return $tpl;
    }

    /**
     * Wraps templates
     * @return  string  rendered template
     */
    private function _tplWrapper() {
        $phs['fd.classPath'] = (!empty($this->configs['cssPath'])) ? ' class="' . $this->configs['cssPath'] . '"' : '';
        $path = $this->_breadcrumbs();
        $phs['fd.path'] = $path;
        $wrapper = !empty($this->_template['wrapper']) ? $this->_template['wrapper'] : '';
        $phs['fd.rows'] = $wrapper;
        if (!empty($this->configs['tplWrapper'])) {
            $tpl = $this->parseTpl($this->configs['tplWrapper'], $phs);
        } else {
            $tpl = $wrapper;
        }

        return $tpl;
    }

    /**
     * Trim the absolute path to be a relatively safe path
     * @param   string  $path   the absolute path
     * @return  string  trimmed path
     */
    private function _trimPath($path) {
        $xPath = @explode(DIRECTORY_SEPARATOR, $this->configs['origDir'][0]);
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
        if (!$this->configs['browseDirectories']) {
            return '';
        }
        $dirs = $this->configs['getDir'];
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
            $trailingPath = trim($trailingPath, DIRECTORY_SEPARATOR);
            $trailingPath .= DIRECTORY_SEPARATOR . $title;
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
                    'fd.title' => $this->modx->lexicon('fd.breadcrumb.home'),
                    'fd.link' => $pageUrl,
                    'fd.url' => $pageUrl,
                    'fd.hash' => '',
                );
            } else {
                $trail[$k] = array(
                    'fd.title' => $title,
                    'fd.link' => $link['url'], // fallback
                    'fd.url' => $link['url'],
                    'fd.hash' => $hash,
                );
            }
            if ($k < ($countTrimmedPathX - 1)) {
                $trailingLink[] = $this->parseTpl($this->configs['tplBreadcrumb'], $trail[$k]);
            } else {
                $trailingLink[] = $title;
            }
        }
        $breadcrumb = @implode($this->configs['breadcrumbSeparator'], $trailingLink);

        return $breadcrumb;
    }

    public function parseTemplate() {
        $o = $this->_tplWrapper();
        return $o;
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
            $this->modx->getOption('core_path')
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
     * Sets the salted parameter to the database
     * @param   string  $ctx        context
     * @param   string  $filename   filename
     * @return  string  hashed parameter
     */
    private function _setHashedParam($ctx, $filename) {
        $input = $this->configs['saltText'] . $ctx . $filename;
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
        if (!$this->modx->loadClass('filedownload.FileDownloadPlugin',$this->configs['modelPath'],true,true)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR,'[FileDownload] could not load plugin class.');
            return false;
        }
        $plugins = new FileDownloadPlugin($this);
        if (!is_array($customProperties))
            $customProperties = array();

        $plugins->setProperties($customProperties);
        return $plugins->getPlugins($eventName, $toString);
    }
}