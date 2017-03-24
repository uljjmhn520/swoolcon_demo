<?php

/** @var \Phalcon\Mvc\Router $router */


$di->setShared('router',function() use($di){
    $router = new \Phalcon\Mvc\Router();

    $router->setDI($di);

    //$router->handle();

    return $router;
});

