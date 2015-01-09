<?php

// Autoloader
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
