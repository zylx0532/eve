<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Doctrine\Common\Annotations\AnnotationRegistry;
use Dotenv\Dotenv;

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__ . '/../vendor/autoload.php';

AnnotationRegistry::registerLoader([$loader, 'loadClass']);

$dotenv = new Dotenv(dirname(__DIR__));
$dotenv->load();
$dotenv->required([
    'SYMFONY_ENV',
    'SYMFONY_DEBUG',
    'SYMFONY_SECRET',
    'EVE_CLIENT_ID',
    'EVE_SECRET_KEY',
    'EVE_CALLBACK_URL',
]);

return $loader;
