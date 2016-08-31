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
 * Class for custom plugin's events
 *
 * @author goldsky <goldsky@virtudraft.com>
 * @package main class
 */
class FileDownloadPlugin
{

    public $modx;
    public $configs;
    public $fileDownload;
    public $errors = array();
    private $_event;
    private $_appliedEvents = array();
    private $_allEvents = array();
    private $_properties = array();

    public function __construct(FileDownloadR &$fileDownload)
    {
        $this->modx = &$fileDownload->modx;
        $this->configs = $fileDownload->config;
        $this->fileDownload = $fileDownload;
        $this->preparePlugins();
    }

    /**
     * Arrange the $scriptProperties, plugins and events correlation, then fill
     * the $appliedEvents property
     * @return void $this->_appliedEvents
     */
    public function preparePlugins()
    {
        $this->_allEvents = include $this->configs['corePath'] . 'elements/plugins/filedownloadplugin.events.php';
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
     * @param   string $key key
     * @param   string $val value
     * @return  void
     */
    public function setProperty($key, $val)
    {
        $this->_properties[$key] = $val;
    }

    /**
     * Set custom properties for the plugin in the run time in an array of
     * key => value pairings
     * @param   array $array array of the properties
     * @return  void
     */
    public function setProperties(array $array = array())
    {
        foreach ($array as $key => $val) {
            $this->setProperty($key, $val);
        }
    }

    public function getProperty($key)
    {
        return $this->_properties[$key];
    }

    public function getProperties()
    {
        return $this->_properties;
    }

    /**
     * Set custom config for the class in the run time
     * @param   string $key key
     * @param   string $val value
     * @return  void
     */
    public function setConfig($key, $val)
    {
        $this->configs = array_merge($this->configs, array($key => $val));
    }

    /**
     * Set custom config for the class in the run time in an array of
     * key => value pairings
     * @param   array $array array of the properties
     * @return  void
     */
    public function setConfigs(array $array = array())
    {
        foreach ($array as $key => $val) {
            $this->setConfig($key, $val);
        }
    }

    public function getConfig($key)
    {
        return $this->configs[$key];
    }

    public function getConfigs()
    {
        return $this->configs;
    }

    public function getAllEvents()
    {
        return $this->_allEvents;
    }

    public function getAppliedEvents()
    {
        return $this->_appliedEvents;
    }

    public function getEvent()
    {
        return $this->_event;
    }

    /**
     * Get all plugins, with the strict option if it is enabled by the snippet
     * @param   string $eventName name of the event
     * @param   boolean $toString return the results as string instead
     * @return  boolean|array   false | plugin's output array
     */
    public function getPlugins($eventName, $toString = false)
    {
        $this->_event = $eventName;
        $output = array();
        if (empty($this->_appliedEvents[$eventName]) ||
            !is_array($this->_appliedEvents[$eventName])
        ) {
            return false;
        }
        foreach ($this->_appliedEvents[$eventName] as $plugin) {
            $loaded = $this->_loadPlugin($plugin);
            if (!$loaded) {
                if (!empty($plugin['strict'])) {
                    $this->modx->log(modX::LOG_LEVEL_ERROR, $eventName . ' of <b>' . $plugin . '</b> returned false.', '', 'FileDownloadPlugin');
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

    private function _loadPlugin($plugin)
    {
        $pluginName = $plugin['name'];
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
                $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not find plugin "' . $pluginName . '".', '', 'FileDownloadPlugin');
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Attempt to load a file-based plugin given a name
     * @param string $path The absolute path of the plugin file
     * @return boolean True if the plugin succeeded
     */
    private function _loadFileBasedPlugin($path)
    {
        $modx = &$this->modx;
        $fileDownload = &$this->fileDownload;
        $plugin = &$this;
        $errors = &$this->errors;
        $success = false;
        try {
            $success = include $path;
        } catch (Exception $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, $e->getMessage(), '', 'FileDownloadPlugin');
        }
        return $success;
    }

}