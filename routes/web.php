<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->post('/', 'TelegramController@handle');

$router->get('/', function () {
    return 'а что ты тут ожидал увидеть?)';
});
