<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/login', 'Auth::login');
$routes->post('/login', 'Auth::attempt', ['filter' => 'loginThrottle']);
$routes->get('/setup', 'Auth::setup');
$routes->post('/setup', 'Auth::createOwner');
$routes->post('/logout', 'Auth::logout');
$routes->get('/', 'Home::index', ['filter' => 'auth']);

$routes->group('api/v1', ['filter' => 'auth'], static function ($routes): void {
	$routes->get('workspace', 'Api\Workspace::index', ['filter' => 'permission:stock.view']);
	$routes->post('products', 'Api\Workspace::createProduct', ['filter' => 'permission:product.manage']);
	$routes->post('stock-movements', 'Api\Workspace::adjustStock', ['filter' => 'permission:stock.adjust']);
	$routes->post('transactions', 'Api\Workspace::checkout', ['filter' => 'permission:pos.sell']);
	$routes->post('suppliers', 'Api\Workspace::createSupplier', ['filter' => 'permission:supplier.manage']);
	$routes->post('expenses', 'Api\Workspace::createExpense', ['filter' => 'permission:cash.manage']);
	$routes->post('debts', 'Api\Workspace::createDebt', ['filter' => 'permission:finance.manage']);
	$routes->post('debt-payments', 'Api\Workspace::payDebt', ['filter' => 'permission:finance.manage']);
	$routes->post('report-archives', 'Api\Workspace::archiveReport', ['filter' => 'permission:report.archive']);
});
