<?php

namespace db;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Events\Dispatcher;

class Connection {

    public static function createConn() {
        $capsule = new DB;
        $capsule->addConnection(parse_ini_file(__DIR__ . '/../../config/config.ini'));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }
}
