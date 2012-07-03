<?php

require_once __DIR__ . "/../../vendor/autoload.php";
require_once "Calculator.php";

$calculator = new Calculator;

$engine = new \Context\Engine();
$engine->addParamConverter(new \Context\ParamConverter\StringToArrayConverter());
$engine->addInputSource(new \Context\Input\GetOptInput());

$stats = $engine->execute(array(
    'context' => array($calculator, 'statistics'),
    'shortOptions' => '',
    'longOptions' => array('numbers:'),
));

var_dump($stats);

