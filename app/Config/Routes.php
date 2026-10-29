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

// The Vault Account read path (ticket #11) and write path (ticket #12).
// account/index is the sidebar's href — CI3 auto-routing served it, too.
// Auto-routing answered any verb; the write routes carry its meaningful
// GET/POST pair — other verbs 404, the one narrowing the port accepts.
$routes->get('account', 'Account::index');
$routes->get('account/index', 'Account::index');
$routes->get('account/detail_account/(:segment)', 'Account::detailAccount/$1');
$routes->match(['get', 'post'], 'account/add_account', 'Account::addAccount');
$routes->match(['get', 'post'], 'account/add', 'Account::add');
$routes->match(['get', 'post'], 'account/update/(:segment)', 'Account::update/$1');
$routes->match(['get', 'post'], 'account/delete/(:segment)', 'Account::delete/$1');
// The bare account/delete is deliberately un-routed: CI3 auto-routing ran
// delete(null) there — no row matched, yet the flash still said "Delete
// account successfully." and the list followed. It 404s here instead
// (issue #30, with ADR-0004's simpler surface), pinned in AccountWriteTest.

// The settings pages (ticket #14) — `setting` is the sidebar's href.
// `Setting` is the change-password page's breadcrumb spelling: CI3 matched
// controller names case-insensitively (ucfirst), so both spellings served;
// CI4 matches exactly, so the alias is registered.
$routes->get('setting', 'Setting::index');
$routes->get('Setting', 'Setting::index');
$routes->match(['get', 'post'], 'setting/update/(:segment)', 'Setting::update/$1');
$routes->get('setting/password/(:segment)', 'Setting::password/$1');
$routes->match(['get', 'post'], 'setting/setpassword/(:segment)', 'Setting::setPassword/$1');
$routes->match(['get', 'post'], 'setting/deactivate/(:segment)', 'Setting::deactivate/$1');
$routes->match(['get', 'post'], 'setting/delete/(:segment)', 'Setting::delete/$1');
