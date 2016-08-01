<?php

class Opac extends \Simbio\SimbioController {
    /**
     * MUST call parent constructor
     *
     **/
    public function __construct($apps_config) {
        parent::__construct($apps_config);
    }

    /**
     * Default route, will be called when there is request to "http://localhost/my-web-project-dir/index.php/Opac
     *
     **/
    public function index() {
        // load model class located in ./apps/modules/Opac/models/Opac_model.php
        $this->loadModel('Opac_model');
        // run model method
        $data = $this->Opac_model->getData();
        // load view
        $this->loadView('home');
    }
}