<?php namespace Simbio;

/**
 * Simbio URL routing class
 *
 * Copyright (C) 2016 Arie Nugraha (dicarve@gmail.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * Usage:
 * On your application root directory (usually in "index.php") just write:
 *
 * <?php
 * require __DIR__.'/vendor/autoload.php';
 *
 * $simbio = new Simbio\Simbio;
 * try {
 *    $simbio->route();
 * } catch (Exception $error) {
 *    exit('Error : '.$error->getMessage());
 * }
 *
 */

class Simbio
{
  const APPS_CONFIG_DIR = './apps/config/';
  const APPS_MODULES_DIR = './apps/modules/';
  const APPS_VIEWS_DIR = './apps/modules/<modulename>/views/';
  const APPS_MODELS_DIR = './apps/modules/<modulename>/models/';

  private $config = array();
  private $db = false;
  private $base_dir = '';
  private $base_url = '';
  private $doc_root_dir = '/';
  private $protocol = 'http';
  private $port = 80;
  private $site_domain = '';
  private $site_base_url = '';

  public function __construct() {
    // load configurations
    $this->loadConfig();

	// get information related to path and URL
	$this->base_dir  		= __DIR__;
	// $this->doc_root_dir 	= $this->config['site_base_path'] = preg_replace("!${_SERVER['SCRIPT_NAME']}$!", '', $_SERVER['SCRIPT_FILENAME']);
	$this->doc_root_dir 	= $this->config['site_base_path'] = str_replace(basename($_SERVER['SCRIPT_FILENAME']), '', $_SERVER['SCRIPT_FILENAME']);
	$this->base_url  		= $this->config['base_url'] = dirname($_SERVER['REQUEST_URI']);
	$this->protocol  		= empty($_SERVER['HTTPS']) ? 'http' : 'https';
	$this->port      		= $_SERVER['SERVER_PORT'];
	$port_str 				= ($this->protocol == 'http' && $this->port == 80 || $this->protocol == 'https' && $this->port == 443) ? '' : ":".$this->port;
	$this->site_domain  	= $_SERVER['SERVER_NAME'];
	$this->site_base_url  	= $this->config['site_base_url'] = $this->protocol.'://'.$this->site_domain.$port_str.$this->base_url;
  }

  /**
   * Load configuration files from "config" folder
   *
   **/
  private function loadConfig() {
    foreach (new \DirectoryIterator(self::APPS_CONFIG_DIR) as $fileInfo) {
        if ($fileInfo->isDot()) continue;
        if ($fileInfo->isDir()) continue;
		if (strpos($fileInfo->getPathname(), '.php') === false) {
		  continue;
		}
        require $fileInfo->getPathname();
    }
    $this->config = $sysconf;
  }

  /**
   * Route URI to module
   *
   **/
  public function route() {
    // get request URI
    $current_module = '';
    $request = $this->getRequest();
  	$routes = array();
	$routes_array = explode('/', $request);
	foreach($routes_array as $route)
	{
      if(trim($route) != '')
        $routes[] = $route;
	}

    // create module instance
    if (isset($routes[0])) {
      $current_module = ucfirst($routes[0]);
      $current_method = 'index';
      $module_file = self::APPS_MODULES_DIR.$current_module.'/'.$current_module.'.php';
      if (!file_exists($module_file)) {
        header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);
        throw new \Exception('Module '.$current_module.' not found, please check if its already installed yet.');
        exit();
      } else {
        require_once $module_file;
        $this->{$current_module} = new $current_module($this->config);
      }
    } else {
      // try last chance to load default module
      $current_module = ucfirst($this->config['default_module']);
      $module_file = self::APPS_MODULES_DIR.$current_module.'/'.$current_module.'.php';
      if (!file_exists($module_file)) {
        header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);
        throw new \Exception('Default module '.$current_module.' not found, please check if its already installed yet.');
        exit();
      } else {
        require_once $module_file;
        $this->{$current_module} = new $current_module($this->config);
      }
    }

    // call method or class of module
    $args = $routes;
    if (isset($routes[1])) {
      // call method
      $current_method = $routes[1];
	  $tmp_module = ucfirst($current_method);
	  // check if it also a class
	  $class_file = self::APPS_MODULES_DIR.$current_module.'/'.$tmp_module.'.php';
	  if (file_exists($class_file)) {
		require_once $class_file;
		$this->{$tmp_module} = new $tmp_module;
  		// get arguments
		if (isset($routes[2])) {
		  $tmp_method = $routes[2];
		  if (!method_exists($this->{$tmp_module}, $tmp_method)) {
			header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);
			throw new \Exception('Method '.$tmp_method.' not found on module '.$tmp_module);
			exit();
		  }
		  // get arguments
		  if (isset($routes[3])) {
			unset($args[0], $args[1], $args[2]);
			call_user_func_array(array($this->{$tmp_module}, $tmp_method), $args);
		  } else {
			$this->{$current_module}->{$current_method}();
		  }
		} else {
		  $this->{$tmp_module}->index();
		}
	  } else {
		// check method
		if (!method_exists($this->{$current_module}, $current_method)) {
		  header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);
		  throw new \Exception('Method '.$current_method.' not found on module '.$current_module);
		  exit();
		}
		// get arguments
		if (isset($routes[2])) {
		  unset($args[0], $args[1]);
		  call_user_func_array(array($this->{$current_module}, $current_method), $args);
		} else {
		  $this->{$current_module}->{$current_method}();
		}
	  }
    } else {
      // check method
      if (!method_exists($this->{$current_module}, 'index')) {
        header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);
        throw new \Exception('Default method "index" not found on module '.$current_module);
        exit();
      }
      $this->{$current_module}->index();
    }

	// get all views and load it
	$views = $this->{$current_module}->getModuleViews();
	echo implode('', $views);
  }

  /**
   * Helper class to parse request URI
   *
   **/
  private function getRequest()
  {
    $basepath = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
	$uri = substr($_SERVER['REQUEST_URI'], strlen($basepath));
    // remove index.php
    $uri = preg_replace('@\/?index\.php\/?@i', '', $uri);
	return $uri;
  }
}
