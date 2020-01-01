<?php

$router = $di->getRouter();

$router->add('/cities', array( 
    'controller' => 'weather', 
    'action' => 'getWeather', 
 ));

 $router->addPost('/weather', array( 
    'controller' => 'weather', 
    'action' => 'index', 
 ));

$router->handle($_SERVER['REQUEST_URI']);
