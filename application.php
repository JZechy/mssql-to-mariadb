<?php declare(strict_types=1);

require __DIR__ . "/vendor/autoload.php";
require __DIR__ . "/src/Bootstrap.php";

$bootstrap = new \Zet\DbMigration\Bootstrap();
$bootstrap->run();