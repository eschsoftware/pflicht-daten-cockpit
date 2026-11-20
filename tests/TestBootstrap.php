<?php declare(strict_types=1);

use Shopware\Core\TestBootstrapper;

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '1';
putenv('APP_ENV=test');
putenv('APP_DEBUG=1');

$loader = (new TestBootstrapper())
    ->addCallingPlugin()
    ->addActivePlugins('EschSoftwarePflichtDatenCockpit')
    ->setForceInstallPlugins(true)
    ->bootstrap()
    ->getClassLoader();

$loader->addPsr4('EschSoftware\\PflichtDatenCockpit\\Test\\', __DIR__);
