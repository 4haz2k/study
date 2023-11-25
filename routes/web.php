<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->post(env('TOKEN').'/webhook', 'TelegramController@handle');
