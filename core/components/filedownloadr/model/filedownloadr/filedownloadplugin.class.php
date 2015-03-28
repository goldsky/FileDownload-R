<?php

class FileDownloadPlugin {

    public $modx;
    public $configs;
    public $fileDownload;
    public $errors = array();
    private $_event;
    private $_appliedEvents = array();
    private $_allEvents = array();
    private $_properties = array();

    public function __construct(FileDownloadR &$fileDownload) {
        $this->modx = &$fileDownload->modx;
        $this->configs = $fileDownload->configs;
        $this->fileDownload = $fileDownload;
        $this->preparePlugins();
    }

    /**
     * Arrange the $scriptProperties, plugins and events correlation, then fill
     * the $appliedEvents property
     * @return void $this->_appliedEvents
     */
    public function preparePlugins() {
        $this->_allEvents = include $this->configs['basePath'] . 'plugins/filedownloadplugin.events.php';
        $jPlugins = json_decode($this->configs['plugins'], 1);
        if (empty($jPlugins))
            return;
        
        foreach ($jPlugins as $v) {
            $this->_appliedEvents[$v['event']][] = $v;
        }
        foreach ($this->_allEvents as $i => $event) {
            if (isset($this->_appliedEvents[$i]))
                $this->_allEvents[$i] = $this->_appliedEvents[$i];
        }
    }

    /**
     * Set custom property for the plugin in the run time
     * @param   string  $key    key
     * @param   string  $val    value
     * @return  void
     */
    public function setProperty($key, $val) {
        $this->_properties[$key] = $val;
    }

    /**
     * Set custom properties for the plugin in the run time in an array of
     * key => value pairings
     * @param   array   $array  array of the properties
     * @return  void
     */
    public function setProperties(array $array = array()) {
        foreach ($array as $key => $val) {
            $this->setProperty($key, $val);
        }
    }

    public function getProperty($key) {
        return $this->_properties[$key];
    }

    public function getProperties() {
        return $this->_properties;
    }

    /**
     * Set custom config for the class in the run time
     * @param   string  $key    key
     * @param   string  $val    value
     * @return  void
     */
    public function setConfig($key, $val) {
        $this->configs = array_merge($this->configs, array($key => $val));
    }

    /**
     * Set custom config for the class in the run time in an array of
     * key => value pairings
     * @param   array   $array  array of the properties
     * @return  void
     */
    public function setConfigs(array $array = array()) {
        foreach ($array as $key => $val) {
            $this->setConfig($key, $val);
        }
    }

    public function getConfig($key) {
        return $this->configs[$key];
    }

    public function getConfigs() {
        return $this->configs;
    }

    public function getAllEvents() {
        return $this->_allEvents;
    }

    public function getAppliedEvents() {
        return $this->_appliedEvents;
    }

    public function getEvent() {
        return $this->_event;
    }

    /**
     * Get all plugins, with the strict option if it is enabled by the snippet
     * @param   string          $eventName  name of the event
     * @param   boolean         $toString   return the results as string instead
     * @return  boolean|array   false | plugin's output array
     */
    public function getPlugins($eventName, $toString = false) {
        $this->_event = $eventName;
        $output = array();
        if (empty($this->_appliedEvents[$eventName]) ||
                !is_array($this->_appliedEvents[$eventName])) {
            return;
        }
        foreach ($this->_appliedEvents[$eventName] as $plugin) {
            $loaded = $this->_loadPlugin($plugin);
            if (!$loaded) {
                if (!empty($plugin['strict'])) {
                    $this->modx->log(modX::LOG_LEVEL_ERROR, '[FileDownloadPlugin] ' . $eventName . ' of <b>' . $plugin . '</b> returned false.');
                    return false;
                } else {
                    continue;
                }
            } else {
                $output[] = $loaded;
            }
        }
        if ($toString) {
            $output = @implode("\n", $output);
        }
        return $output;
    }

    private function _loadPlugin($plugin) {
        $pluginName = $plugin['name'];
        $success = false;
        if ($snippet = $this->modx->getObject('modSnippet', array('name' => $pluginName))) {
            /* custom snippet plugin */
            $properties = $this->configs;
            $properties['fileDownload'] = &$this->fileDownload;
            $properties['plugin'] = &$this;
            $properties['errors'] = &$this->errors;
            $success = $snippet->process($properties);
        } else {
            /* search for a file-based plugin */
            $this->modx->parser->processElementTags('', $pluginName, true, true);
            if (file_exists($pluginName)) {
                $success = $this->_loadFileBasedPlugin($pluginName);
            } else {
                /* no plugin found */
                $this->modx->log(modX::LOG_LEVEL_ERROR, '[FileDownloadPlugin] could not find plugin "' . $pluginName . '".');
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Attempt to load a file-based plugin given a name
     * @param string $path The absolute path of the plugin file
     * @param array $customProperties An array of custom properties to run with the plugin
     * @return boolean True if the plugin succeeded
     */
    private function _loadFileBasedPlugin($path) {
        $modx = &$this->modx;
        $fileDownload = &$this->fileDownload;
        $plugin = &$this;
        $errors = &$this->errors;
        $success = false;
        try {
            $success = include $path;
        } catch (Exception $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[FileDownloadPlugin] ' . $e->getMessage());
        }
        return $success;
    }

}