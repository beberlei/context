<?php

if (!($loader = @include(__DIR__ . "/../vendor/autoload.php"))) {
    die("Composer required to install all dependencies.");
}

$loader->add("Context\Tests", __DIR__);
