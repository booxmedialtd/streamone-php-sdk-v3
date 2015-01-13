<?php

session_start();

// Autoloader for source files
spl_autoload_register(function($class)
{
	$path = '../src/';
	$prefix = "StreamOne\\API\\v3\\";
	if (substr($class, 0, strlen($prefix)) === $prefix)
	{
		$file = substr($class, strlen($prefix));
		require_once($path . $file . '.php');
	}
});

// Autoloader for PHPUnit support files
spl_autoload_register(function($class)
{
	$path = 'lib/';
	$prefix = "PHPUnit_";
	$file = $path . $class . '.php';
	if ((substr($class, 0, strlen($prefix)) === $prefix) &&
		file_exists($file))
	{
		require_once($file);
	}
});
