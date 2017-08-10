<?php
namespace framework;
class Template
{
	protected $cacheDir;  	//缓存路径
	protected $tplDir;		//模板路径
	protected $vars = [];	//变量数组
	protected $expireTime  = 3600;//过期时间一天

	public function __construct($cacheDir='./cache',
								  $tplDir='./view',
								  int $expireTime = 10)
	{
		$this->cacheDir = $this->checkDir($cacheDir);
		$this->tplDir 	= $this->checkDir($tplDir);
		$this->expireTime = $expireTime;
	}

	//变量分配
	public function assign($name,$value = null)
	{	
		if (is_array($name)) {
			$this->vars = array_merge($this->vars,$name);//数组合并
		} else {
			$this->vars[$name] = $value;
		}
	}

	public function display($tplFile,$isExtract = true)
	{
		//1拼接模板文件和缓存文件路径名
		$viewFile = $this->tplDir . $tplFile;
		$cacheFile = $this->joinCachePath($tplFile);

		//2 判断模板文件是否存在
		if (!file_exists($viewFile) ) {

			exit('模板文件不存在');
		}

		//3 编译模板文件
		if (!file_exists($cacheFile)||
			filemtime($viewFile) > filemtime($cacheFile)||
			filemtime($cacheFile) + $this->expireTime < time()) {
			$content = $this->compile($viewFile);
			file_put_contents($cacheFile, $content);
		} else {//检测所include的文件是否发生变化
			$this->updateInclude($viewFile);
		}
		
		//4变量分配
		if ($isExtract) {
			extract($this->vars);
			include $cacheFile;
		}
	}

	//跟新包含的模板文件
	protected function updateInclude($viewFile)
	{
		$content = file_get_contents($viewFile);
		$pattern = '/\{include (.+)\}/U';
		preg_match_all($pattern, $content, $matches);
		foreach ($matches[1] as $key => $value) {
			$value = trim($value,'\'"');
			$this->display($value,false);
		}
		//var_dump($matches);
	}

	protected function compile($tplFile)
	{
		//读模板文件
		$content = file_get_contents($tplFile);
		//var_dump($content);

		$rule = [
					'{$%%}'    	     => '<?=$\1;?>',
					'{include %%}'   => "没有用",
				];
		foreach ($rule as $key => $value) {
			$key  =  preg_quote($key,'/');
			$pattern = '/'.str_replace('%%', '(.+)', $key) .'/U';
			if (stripos($pattern, 'include')) {
				$content = preg_replace_callback($pattern, [$this,'parseInclude'], $content);
			} else {
				$content = preg_replace($pattern, $value, $content);
			}
			
		}
		return $content;
	}

	protected function parseInclude($matches)
	{
		// var_dump($matches);
		// 去除单引号和双引号
		$viewFile = trim($matches[1],'\'"');
		$this->display($viewFile,false);//编译
		$cacheFile = $this->joinCachePath($viewFile);//缓存路径
		return "<?php include '$cacheFile';?>";//返回替换内容
	}

	protected function joinCachePath($tplFile)
	{
		return $this->cacheDir . str_replace('.', '_', $tplFile) .'.php';
	}
	protected function checkDir($path)
	{
		$dir = rtrim(str_replace('\\', '/', $path),'/').'/';
		$flag = true;
		if (!is_dir($dir)) {
			$flag =  mkdir($dir,0777,true);
		} elseif (!is_readable($dir) || !is_writable($dir)) {
			$flag = chmod($dir,0777);
		}
		if ($flag) {
			return $dir;
		} else {
			exit('创建目录失败或不可读写');
		}
		
	}
}