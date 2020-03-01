<?php
define('ROOT_PATH', __DIR__);
define('DS', DIRECTORY_SEPARATOR);
define('DATA_PATH', ROOT_PATH.DS.'data');
define('OUTPUT_PATH', DATA_PATH.DS.'output');
require_once ROOT_PATH.'/Autoloader.php';
class Creator {

    private static $_config = [];

    public static function run() {
        self::init();
        self::create();
    }

    public static function create() {
        try {
            $config = self::getConfig();
            $database = new \detailedDesign\DatabaseParser($config['host'], $config['port'], $config['database'], $config['username'], $config['password'], $config['charset']);
            $desc = $database->getDesc();
            $apiDoc = new \detailedDesign\DocumentCreator($desc, $config);
            $apiDoc->create();
        } catch (Exception $ex) {
            echo "create error:\n".$ex->getMessage();
        }

    }

    public static function getConfig() {
        if (!empty(self::$_config)) {
            return self::$_config;
        }
        $content = file_get_contents(ROOT_PATH.DS.'config.json');
        self::$_config = json_decode($content, true);
        return self::$_config;
    }



    public static function init() {
        if (!file_exists(DATA_PATH)) {
            mkdir(DATA_PATH);
        }
        if (!file_exists(OUTPUT_PATH)) {
            mkdir(OUTPUT_PATH);
        }
    }
}

Creator::run($argv);