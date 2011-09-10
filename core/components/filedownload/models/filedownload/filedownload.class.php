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
    public $config = array();
    public $error = array();
    private $_count = array();
    private $_imgType = array();
    private $_template = array();

    public function __construct(modX &$modx, array $config = array()) {
        $this->modx = &$modx;

        $config['getDir'] = $this->_checkPath($config['getDir']);
        $config['getFile'] = $this->_checkPath($config['getFile']);

        $config = $this->replacePropPhs($config);
        $basePath = $this->modx->getOption('core_path') . 'components/filedownload/';
        $assetsUrl = $this->modx->getOption('assets_url') . 'components/filedownload/';
        $this->config = array_merge(array(
            'basePath' => $basePath,
            'corePath' => $basePath,
            'modelPath' => $basePath . 'models/',
            'processorsPath' => $basePath . 'processors/',
            'controllersPath' => $basePath . 'controllers/',
            'chunksPath' => $basePath . 'elements/chunks/',
            'jsUrl' => $assetsUrl . 'js/',
            'cssUrl' => $assetsUrl . 'css/',
            'imgTypeUrl' => $assetsUrl . 'img/filetypes/',
            'assetsUrl' => $assetsUrl,
                ), $config);

        $this->modx->addPackage('filedownload', $this->config['modelPath']);

        $this->modx->getService('lexicon', 'modLexicon');
        if ($this->modx->lexicon) {
            $this->modx->lexicon->load('filedownload:default');
        }

        $this->_imgType = $this->_imgTypeProp();
    }

    /**
     * Get the clean path array and clean up some duplicate slashes
     * @param   string  $paths  multiple paths with comma separated
     * @return  array   Dir paths in an array
     */
    private function _checkPath($paths) {
        if (!empty($paths)) {
            $xPath = @explode(',', $paths);
            $cleanPaths = array();
            foreach ($xPath as $path) {
                $path = trim($path);
                if (empty($path))
                    continue;
                // clean up double slashes
                $pathSlashes = @explode(DIRECTORY_SEPARATOR, $path);
                $pathArray = array();
                foreach ($pathSlashes as $slashed) {
                    if (empty($slashed))
                        continue;
                    $pathArray[] = $slashed;
                }
                $iPath = @implode(DIRECTORY_SEPARATOR, $pathArray);
                $cleanPaths[] = $iPath;
            }
        }

        return $cleanPaths;
    }

    /**
     * Retrieve the content of the given path
     * @param   mixed   $root   The specified root path
     * @return  array   All contents in an array
     */
    public function getContents() {
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
    private function _getDescription($contents) {
        if (empty($this->config['chkDesc']) || empty($contents)) {
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
        if (empty($file) || !realpath($file['filename'])) {
            return FALSE;
        }

        $fdlObj = $this->modx->getObject('FDL', array(
            'ctx' => $file['ctx'],
            'filename' => $file['filename']
                ));
        if ($fdlObj === null) {
            $fdlObj = $this->modx->newObject('FDL');
            $fdlObj->fromArray(array(
                'ctx' => $file['ctx'],
                'filename' => $file['filename'],
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

        $countMergedContents = count($mergedContents);
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
                        modX::LOG_LEVEL_ERROR,
                        '&getDir parameter expects a correct dir path. ' . $rootPath . ' is given.'
                        );
                return FALSE;
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
                $fileType = filetype($fullPath);

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

                    $date = date($this->config['dateFormat'], filemtime($fullPath));
                    $link = $this->_linkDirOpen($fullPath, $checkedDb['hash'], $checkedDb['ctx']);

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
                        'image' => $this->config['imgTypeUrl'] . $imgType,
                        'count' => $checkedDb['count'],
                        'link' => $link['url'],
                        'linkAttribute' => $link['attribute'],
                        'hash' => $checkedDb['hash']
                    );

                    $contents[] = $dir;
                }
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
                    modX::LOG_LEVEL_ERROR,
                    '&getFile parameter expects a correct file path. ' . $path . ' is given.'
                    );
            return FALSE;
        }

        $filetype = filetype($fileRealPath);
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

        $link = $this->_linkFileDownload($checkedDb['filename'], $checkedDb['hash'], $checkedDb['ctx']);

        $info = array(
            'ctx' => $checkedDb['ctx'],
            'fullPath' => $fileRealPath,
            'path' => dirname($fileRealPath),
            'filename' => $baseName,
            'alias' => $alias,
            'type' => filetype($fileRealPath),
            'ext' => $ext,
            'size' => $size,
            'sizeText' => $this->_fileSizeText($size),
            'date' => date($this->config['dateFormat'], filemtime($fileRealPath)),
            'image' => $this->config['imgTypeUrl'] . $imgType,
            'count' => $checkedDb['count'],
            'link' => $link['url'],
            'linkAttribute' => $link['attribute'],
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
        $xPipes = array();
        $xPipes = @explode('|', $path);
        $notation[0] = trim($xPipes[0]);
        $notation[1] = !isset($xPipes[1]) ? '' : trim($xPipes[1]);

        return $notation;
    }

    /**
     * Get chunk from modx or fall back to the default file chunk
     * @param   string  $name   chunk's name
     * @param   array   $phs    placeholders in an array
     */
    private function _getChunk($name, array $properties) {
        $chunk = null;
        if (!isset($this->chunks[$name])) {
            $chunk = $this->_getChunkTpl($name);
            if (empty($chunk)) {
                $chunk = $this->modx->getObject('modChunk', array('name' => $name), true);
                if ($chunk == false)
                    return false;
            }
            $this->chunks[$name] = $chunk->getContent();
        } else {
            $o = $this->chunks[$name];
            $chunk = $this->modx->newObject('modChunk');
            $chunk->setContent($o);
        }
        $chunk->setCacheable(false);
        $properties = $this->replacePropPhs($properties);
        return $chunk->process($properties);
    }

    /**
     * Returns a modChunk object from a template file.
     * @access  private
     * @param   string  $name The name of the Chunk. Will parse to name.chunk.tpl
     * @return  modChunk/boolean Returns the modChunk object if found, otherwise false.
     */
    private function _getChunkTpl($name) {
        $chunk = false;
        $f = $this->config['chunksPath'] . strtolower($name) . '.chunk.tpl';
        if (file_exists($f)) {
            $o = file_get_contents($f);
            $chunk = $this->modx->newObject('modChunk');
            $chunk->set('name', $name);
            $chunk->setContent($o);
        }
        return $chunk;
    }

    public function parseTplCode($code, $phs) {
        $chunk = $this->modx->newObject('modChunk');
        $chunk->setContent($code);
        $chunk->setCacheable(false);
        $phs = $this->replacePropPhs($phs);
        return $chunk->process($phs);
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
        if (empty($this->config['imgLocat'])) {
            return FALSE;
        }
        $fdImagesChunk = $this->modx->getChunk($this->config['imgTypes']);
        $fdImagesChunkX = @explode(',', $fdImagesChunk);
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
            $link['attribute'] = '';
        } else {
            if ($this->config['ajaxMode']) {
                $link['url'] = 'javascript:void(0);';
                $link['attribute'] = ' onclick="fileDownload(\'' . $hash . '\')"';
            } else {
                $args = 'fdlfile=' . $hash;
                $url = $this->modx->makeUrl($this->modx->resource->get('id'), $ctx, $args);
                $link['url'] = $url;
                $link['attribute'] = '';
            }
        }
        return $link;
    }

    /**
     * @todo _linkDirOpen: change the hard coded html to template
     * @param   string  $dirPath    directory's path
     * @param   string  $hash       hash
     * @param   string  $ctx        specifies a context to limit URL generation to.
     * @return  array   the open directory link and the javascript's attribute
     */
    private function _linkDirOpen($dirPath, $hash, $ctx = 'web') {
        if (!$this->config['browseDirectories']) {
            return FALSE;
        }
        $link = array();
        if ($this->config['ajaxMode']) {
            $link['url'] = 'javascript:void(0);';
            $link['attribute'] = ' onclick="dirOpen(\'' . $hash . '\')"';
        } else {
            $args = 'fdldir=' . $hash;
            $url = $this->modx->makeUrl($this->modx->resource->get('id'), $ctx, $args);
            $link['url'] = $url;
            $link['attribute'] = '';
        }
        return $link;
    }

    /**
     * Set the new value to the getDir property to browse inside the clicked
     * directory
     * @param   string  $hash   the hashed link
     * @return  bool    TRUE | FALSE
     */
    public function setDirProp($hash) {
        if (empty($hash)) {
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
            return $modx->error->failure($modx->lexicon('fd.err_save_counter'));
        }

        return TRUE;
    }

    /**
     * @todo downloadFile: push the file to the browser
     * @param type $hash
     * @return type
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
        $filePath = $fdlObj->get('filename');
        $fileName = basename($filePath);
        $path = dirname($path);
        $count = $fdlObj->get('count');

        if ($this->modx->context->key !== $ctx) {
            return FALSE;
        }

        if ($fd = fopen($filePath, "r")) {
            header('Content-type: application/force-download');
            header('Content-Disposition: inline; filename="' . $filePath . '"');
            header('Content-Transfer-Encoding: Binary');
            header('Content-length: ' . filesize($filePath));
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            readfile("$filePath");

            fclose($fd);

            if ($this->config['countDownloads']) {
                // save the new count
                $newCount = $count + 1;
                $fdlObj->set('count', $newCount);
                if ($fdlObj->save() === false) {
                    // @todo downloadFile: lexicon string
                    return $modx->error->failure($modx->lexicon('filedownload.fdl_err_save'));
                }
            }

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

        if (!$this->config['groupByDirectory']) {
            $sort = $this->_groupByType($contents);
        } else {
            foreach ($contents as $k => $file) {
                if (!$this->config['browseDirectories'] && $file['type'] === 'dir') {
                    continue;
                }
                $sortPath[$file['path']][$k] = $file;
            }

            $this->_template['wrapper'] .= '';
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

        foreach ($contents as $k => $file) {
            if (!$this->config['browseDirectories'] && $file['type'] === 'dir') {
                continue;
            }
            $sortType[$file['type']][$k] = $file;
        }

        foreach ($sortType as $k => $file) {
            $sortType[$k] = $this->_sortMultiOrders(
                    $file
                    , $this->config['sortBy']
                    , $this->config['sortOrder']
                    , $this->config['sortOrderNatural']
                    , $this->config['sortByCaseSensitive']
            );
        }

        $sort = array();
        if ($this->config['browseDirectories'] && !empty($sortType['dir'])) {
            $sort['dir'] = $sortType['dir'];
            // template
            $row = 1;
            foreach ($sort['dir'] as $k => $v) {
                $v['class'] = $this->_cssDir($row);
                $this->_template['wrapper'] .= $this->_tplDir($v);
                $row++;
            }
        }

        if (!empty($sortType['file'])) {
            $sort['file'] = $sortType['file'];
            // template
            $row = 1;
            $countFile = count($sort['file']);
            foreach ($sort['file'] as $k => $v) {
                $v['class'] = $this->_cssFile($row, $v['ext']);
                $this->_template['wrapper'] .= $this->_tplFile($v);
                $row++;
            }
        }

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
    private function _sortMultiOrders($array, $index, $order, $natSort=FALSE, $caseSensitive=FALSE) {
        if (!is_array($array) || count($array) < 1) {
            return $array;
        }

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
        if ($this->config['cssExtension']) {
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
            $phs['fd.' . $k] = $v;
        }
        if (!$this->config['ajaxMode']) {

        }
        $tpl = $this->_getChunk($this->config['tplDir'], $phs);

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
        if (!$this->config['ajaxMode']) {

        }
        $tpl = $this->_getChunk($this->config['tplFile'], $phs);

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
        if (!$this->config['ajaxMode']) {

        }
        $phs['fd.class'] = (!empty($this->config['cssGroupDir'])) ? ' class="' . $this->config['cssGroupDir'] . '"' : '';
        $phs['fd.groupDirectory'] = $this->_trimPath($path);
        $tpl = $this->_getChunk($this->config['tplGroupDir'], $phs);

        return $tpl;
    }

    /**
     * Wraps templates
     * @return  string  rendered template
     */
    private function _tplWrapper() {
        $phs['fd.classPath'] = (!empty($this->config['cssPath'])) ? ' class="' . $this->config['cssPath'] . '"' : '';
        $path = $this->_breadcrumbs();
        $phs['fd.path'] = $path;
        $phs['fd.rows'] = $this->_template['wrapper'];
        $tpl = $this->_getChunk($this->config['tplWrapper'], $phs);

        return $tpl;
    }

    /**
     * Trim the absolute path to be a relatively safe path
     * @param   string  $path   the absolute path
     * @return  string  trimmed path
     */
    private function _trimPath($path) {
        $modxCorePath = realpath(MODX_CORE_PATH) . DIRECTORY_SEPARATOR;
        $modxAssetsPath = realpath(MODX_ASSETS_PATH) . DIRECTORY_SEPARATOR;
        $searchCorePath = stristr($path, $modxCorePath);

        $trimmedPath = '';
        if (FALSE !== $searchCorePath) {
            $trimmedPath = str_replace($modxCorePath, '', $path) . DIRECTORY_SEPARATOR;
        } else {
            $trimmedPath = str_replace($modxAssetsPath, '', $path) . DIRECTORY_SEPARATOR;
        }

        return $trimmedPath;
    }

    /**
     * Create a breadcrumbs link
     * @param   void
     * @return  string  a breadcrumbs link
     */
    private function _breadcrumbs() {
        if (!$this->config['browseDirectories']) {
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
        $trailingLink = '';
        $countTrimmedPathX = count($trimmedPathX);
        foreach ($trimmedPathX as $k => $breadcrumb) {
            $trailingPath = trim($trailingPath, DIRECTORY_SEPARATOR);
            $trailingPath .= DIRECTORY_SEPARATOR . $breadcrumb;
            $fdlObj = $this->modx->getObject('FDL', array(
                'filename' => $trailingPath
                    ));
            if (!$fdlObj) {
                continue;
            }

            $hash = $fdlObj->get('hash');
            $link = $this->_linkDirOpen($trailingPath, $hash);
            $trail[$k] = array(
                'title' => $breadcrumb,
                'link' => $link['url'],
                'linkAttribute' => $link['attribute']
            );
            if ($k < $countTrimmedPathX - 1) {
                $trailingLink .= '<a href="' . $link['url'] . $link['attribute'] . '">' . $breadcrumb . '</a> ' . DIRECTORY_SEPARATOR . ' ';
            } else {
                $trailingLink .= $breadcrumb;
            }
        }

        return $trailingLink;
    }

    public function parseTemplate() {
        $o = $this->_tplWrapper();
        return $o;
    }

    /**
     * Replace the property's placholders
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
     * @todo _controllerResource
     * @param   int $pageId page id number to the ajax controller resource
     */
    private function _controllerResource($pageId) {
        if (!is_numeric($pageId)) {
            // @todo _controllerResource: lexicon
            $this->error[] = $this->modx->lexicon('FileDownload snippet expects &ajaxControllerPage to be a numeric');
            return FALSE;
        }
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

}