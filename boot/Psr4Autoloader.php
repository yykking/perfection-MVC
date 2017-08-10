<?php
class Psr4Autoloader
{
	//当命名空间和路径不一致时，做对照
	
	protected $namespaces = [];
	public function __construct($config=null)
	{
		spl_autoload_register([$this,'loadClass']);
		if (is_array($config)) {
			$this->addNamespace($config);
		}
	}
	function loadClass($className)
	{
	 	
	 	//取明空间
	 	$arr = explode('\\', $className);
	 	//真实类名
	 	$realClass = array_pop($arr);
	 	//名空间
	 	$namespace = join('\\',$arr) . '\\';
	 	$this->loadMapFile($namespace,$realClass);
	}

	//根据命名空间和类名完成自动加载
	protected function loadMapFile($namespace,$realClass)
	{
		//1如果名空间在对照表里，则取出路径拼接+类名+.php
		if (!empty($this->namespaces[$namespace])) {

			foreach ($this->namespaces[$namespace] as $key => $value) {
				$file = $value .$realClass . '.php';
				if (file_exists($file)) {
					break;
				}
			}
		} else {//2 如果没有则正常加载
		
			$file = rtrim(str_replace('\\', '/', $namespace),'/').'/' . $realClass . '.php';
		}
		// var_dump($file);
		if (file_exists($file)) {
			include $file;
			return true;
		}
		return false;
	 	
	}

	public function addNamespace($namespace,$realPath=null)
	{
		if (is_array($namespace)) {
			foreach ($namespace as $key => $value) {
				$this->addElement($value,$key);
			}
		} else {
			$this->addElement($namespace,$realPath);
		}
	}

	protected function addElement($namespace,$realPath)
	{
		//先去掉两边的反斜线，在后面添加反斜线
		$namespace = trim($namespace,'\\') .'\\';
		$realPath = str_replace('\\', '/', $realPath);
		$realPath = trim($realPath,'/') .'/';
		//一个名空间对应多个路径
		$this->namespaces[$namespace][] = $realPath;
	}
	
}