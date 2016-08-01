<?php namespace Simbio;

/**
 * Simbio abstract controller class
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
 */

abstract class SimbioController
{
  protected $config = array();
  protected $db = false;
  protected $views = array();
  private $site_base_url = '';
  private $site_base_path = '';
  private $loaded_models = array();

  public function __construct($config) {
	$this->config = $config;
	if (isset($this->config['db']['autoload']) && $this->config['db']['autoload']) {
	  // connect to database
	  $this->connectDB($this->config['db']['dsn'],
		$this->config['db']['username'],
		$this->config['db']['password']);
	}
  }

  /**
   * Load view
   *
   **/
  protected function loadView($view_path, $view_data = array(), $bool_return = false) {
	if (strpos($view_path, '/') === false) {
	  $module = get_class($this);
	  $file = $view_path.'.php';
	} else {
	  $view_comp = explode('/', $view_path);
	  $comp_count = count($view_comp);
	  // get module name
	  $module = $view_comp[0];
	  // get view name
	  $view = $view_comp[$comp_count-1];
	  $view_subdir_path = str_replace($module.'/', '', $view_path);
	  $file = str_replace($module.'/', '', $view_path).'.php';
	}
	$view_file = str_replace('<modulename>', $module, \Simbio\Simbio::APPS_VIEWS_DIR).$file;

	ob_start();
	if (file_exists($view_file)) {
	  require $view_file;
	} else {
	  throw new \Exception('Cannot found view '.$view_file);
	}
	$string_view = ob_get_clean();
	if ($bool_return) {
	  return $string_view;
	} else {
	  $this->views[] = $string_view;
	}
  }

  /**
   * Load model
   *
   **/
  protected function loadModel($model_path) {
	if (strpos($model_path, '/') === false) {
	  // get module name
	  $module = get_class($this);
	  // get model name
	  $model = $model_path;
	  $model_subdir_path = $model_path;
	  $model_filepath = str_replace('<modulename>', $module, \Simbio\Simbio::APPS_MODELS_DIR).$model_subdir_path.'.php';
	} else {
	  // explode $model_path into component
	  $model_comp = explode('/', $model_path);
	  $comp_count = count($model_comp);
	  // get module name
	  $module = $model_comp[0];
	  // get model name
	  $model = $model_comp[$comp_count-1];
	  $model_subdir_path = str_replace($module.'/', '', $model_path);
	}
	$model_filepath = str_replace('<modulename>', $module, \Simbio\Simbio::APPS_MODELS_DIR).$model_subdir_path.'.php';

	if (file_exists($model_filepath)) {
	  // register loaded model to memory
	  $this->loaded_models[$model] = $model;
	  // require the class
	  require_once $model_filepath;
	  $this->{$model} = new $model($this->config);
	  if ($this->db) {
		$this->{$model}->setDB($this->db);
	  }
	  return $this->{$model};
	} else {
	  throw new \Exception('Cannot found model '.$model_filepath);
	  return false;
	}
  }

  /**
   * Database Connection
   *
   **/
  protected function connectDB($dsn, $username, $password) {
    // dsn: 'mysql:host=localhost;dbname=testdb;charset=utf8mb4'
    $this->db = new \PDO($dsn, $username, $password);
    $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $this->db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
	return $this->db;
  }

  /**
   * Return array of all module views
   *
   **/
  public function getModuleViews() {
	return $this->views;
  }

  /**
   * Return full web URL of path
   *
   **/
  public function url($path) {
	// remove slash at the front of path
	$path = preg_replace('@^/@i', '', $path);
	return $this->config['site_base_url'].'/'.$path;
  }

  /**
   * Return base url of application
   *
   **/
  public function baseUrl() {
	return $this->config['base_url'];
  }

  /**
   * Return site root/base url of application
   *
   **/
  public function siteBaseUrl() {
	return $this->config['site_base_url'];
  }

  /**
   * Return site base url of application
   *
   **/
  public function basePath() {
	return $this->config['site_base_path'];
  }
}
