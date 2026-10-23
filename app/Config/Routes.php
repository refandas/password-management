<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Mirrors the legacy URL space (pre-migration routes.php: default controller
// `auth`; every other path auto-routed controller/method). Routes appear here
// as tickets port their controllers — unported paths 404 until then.
$routes->get('/', 'Auth::index');
$routes->match(['get', 'post'], 'auth', 'Auth::index');
$routes->match(['get', 'post'], 'auth/login', 'Auth::login');
$routes->match(['get', 'post'], 'auth/register', 'Auth::register');
$routes->match(['get', 'post'], 'auth/create', 'Auth::create');
$routes->match(['get', 'post'], 'auth/forgotpassword', 'Auth::forgotPassword');
$routes->match(['get', 'post'], 'auth/resetpassword', 'Auth::resetPassword');
$routes->match(['get', 'post'], 'auth/setpassword/(:segment)', 'Auth::setPassword/$1');
$routes->match(['get', 'post'], 'auth/activateuser/(:segment)', 'Auth::activateUser/$1');
$routes->match(['get', 'post'], 'auth/deactivateuser/(:segment)', 'Auth::deactivateUser/$1');

$routes->get('dashboard', 'Dashboard::index');
$routes->get('dashboard/users', 'Dashboard::users');
$routes->get('dashboard/user/(:segment)', 'Dashboard::user/$1');
$routes->get('dashboard/logout', 'Dashboard::logout');
