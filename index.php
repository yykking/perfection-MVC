<?php

class Psr4Auloader
{
	//为了解决明空间和目录不一致，创建一个名空间和目录的对照
	//一个名空间对多个路径
	protected $namespaces = []; 

	public function __construct()
	{
		spl_autoload_register([$this,'loadClass']);
	}

	//自动加载
	function loadClass($className)
	{
		//1获得名空间和类名
		$arr = explode('\\', $className);
		$realClass = array_pop($arr);
		$namespace = join('\\',$arr) . '\\';
		var_dump($namespace,$realClass);

		//根据名空间和类名完成自动加载
		$this->loadMapFile($namespace,$realClass);
	}

	protected function loadMapFile($namespace,$realClass)
	{
		if (empty($this->namespaces[$namespace])) {//正常处理
			$file = rtrim(str_replace('\\', '/', $namespace),'/').'/'.$realClass . '.php';
		} else {
			foreach ($this->namespaces[$namespace] as $key => $value) {
				$file = rtrim(str_replace('\\', '/', $value),'/').'/' .$realClass . '.php';
				if (file_exists($file)) {
					break;
				}
			}
		}

		if (file_exists($file)) {
			include $file;
			return true;
		}
		return false;
	}


	public function addNamespace($namespace,$realPath)
	{
		$this->namespaces[$namespace][] = $realPath;
	}
	
}
//  index\controller   app/index/controller

$loader = new Psr4Auloader();
$loader->addNamespace('index\\controller\\','app/index/controller/');
var_dump($loader);

$obj = new \index\controller\Controller();
$obj->index();
