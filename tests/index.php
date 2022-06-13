<?php

use Bubu\Router\Router;

require '../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable('../');
$dotenv->load();

Router::controllerNamespace('Bubu\\Account\\Api');

Router::get('/validEmail/:code', 'AccountUpdate#verifyEmail');


Router::post('/api/account/update/email', 'AccountUpdate#email');

Router::post('/api/account/register', 'RegisterApi#register');

Router::post('/api/account/login', 'LoginApi#login');

Router::post('/api/account/update/token', 'AccountUpdate#token');

Router::post('/api/account/update/password', 'AccountUpdate#password');

Router::check();