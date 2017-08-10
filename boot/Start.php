<?php

include 'boot/Psr4Autoloader.php';

class Start
{
	protected static $loader;
	public static function init()
	{
		session_start();//开启session
		$config = include 'config/namespace.php';
		self::$loader = new Psr4Autoloader($config);
	}

	public static function route()
	{
		//index.php?m=index&c=index&a=index
		//路由
		//m module  模块 : 前台或后台（index\admin)
		//c controller  控制器
		//a action 方法

		$_GET['m'] = empty($_GET['m'])? 'index' : $_GET['m'];
		$_GET['c'] = empty($_GET['c'])? 'Index' : ucfirst($_GET['c']);
		$_GET['a'] = empty($_GET['a'])? 'index' : $_GET['a'];

		$c = $_GET['m'] .'\\controller\\'.$_GET['c'];

		//调用累的方法，请使用数组[对象，‘方法’]
		call_user_func([new $c(),$_GET['a']]);
	}
}

