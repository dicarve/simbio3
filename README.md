# Simbio 3
Simbio 3 is a simple PHP framework used mainly by SLiMS (Senayan Library Management System)  project

## Installation
To install Simbio in your web project just run Composer command line inside your web project directory:

    composer require simbio/simbio3

Or if you install manually without using Composer just put all Simbio source code inside some directory (example: simbio3) and include it at top of index.php:

    <?php
    require_once 'simbio3/Simbio.php'
    require_once 'simbio3/SimbioController.php'

### Autoload configuration
To make all Simbio classes loaded automatically by composer you need to edit composer.json file on your web project directory so it will looks like below:

	{
	    "require": {
	        "simbio/simbio3": "*"
	    },
	    "autoload": {
	        "psr-4": {
	            "Simbio\\": "./vendor/simbio/simbio3/src/"
	        }
	    }
	}

after you change the composer.json file you need to reload composer autoload configuration index to make it work by invoking below command in command line:

	composer dump-autoload -o

## Using Simbio router class
Create index.php on your web project root directory with below code:

    <?php
    require __DIR__.'/vendor/autoload.php';

    $simbio = new Simbio\Simbio;
    try {
        $simbio->route();
    } catch (Exception $error) {
        exit('Error : '.$error->getMessage());
    }

create minimum directory structure like below :

    /my-web-project-dir/apps/
    /my-web-project-dir/apps/modules/
    /my-web-project-dir/apps/config/
    /my-web-project-dir/apps/themes/

## Create an application module
To create a module for your web project just follow below simple step:

- Create directory under `/my-web-project-dir/apps/modules`. For example to for module "Bibliography" the directory will be `/my-web-project-dir/apps/modules/Bibliography`,the first letter MUST be an uppercase
- Inside `Bibliography` module directory, Create file named `Bibliography.php` containing `Bibliography` class definition
- Create `views` and `models` directory inside `Bibliography` directory
- Inside `Bibliography` class you must write minimum code like below:


Bibliography controller source code:

    <?php
    class Bibliography extends \Simbio\SimbioController {
    	 /**
    	  * The parent class \Simbio\SimbioController constructor MUST be
    	  * also invoked in controller constructor
    	  *
    	  **/
        public function __construct($apps_config) {
            parent::__construct($apps_config);
        }

        /**
         * Default route, will be called when there is request to "http://localhost/my-web-project-dir/index.php/Bibliography
         *
         **/
        public function index() {
            echo 'Hello! this is Simbio 3 framework';
        }

        /**
         * Route for request: "http://localhost/my-web-project-dir/index.php/Bibliography/save/1"
         *
         **/
        public function save($id) {
            // SimbioController already provide $this->db PDO's object for database operation
            $stmt = $this->db->prepare("UPDATE biblio SET title=? WHERE biblio_id=?");
            $stmt->bindValue(1, 'New updated title', PDO::PARAM_STR);
            $stmt->bindValue(2, $id, PDO::PARAM_INT);
            $stmt->execute();
            // load view "update" from inside "Bibliography" module directory
            $this->loadView('update');
        }
    }

