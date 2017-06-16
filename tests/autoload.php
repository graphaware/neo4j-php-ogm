<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

error_reporting(E_ALL | E_STRICT);
$basedir = __DIR__.'/../';
$proxyDir = $basedir.DIRECTORY_SEPARATOR.'_var';
putenv("basedir=$basedir");
putenv("proxydir=$proxyDir");
$loader = require_once __DIR__.'/../vendor/autoload.php';
