<?php 
namespace framework;
/**
 * @abstract:   文件上传类
 * @author:     成少雷
 * @date:       2017-04-10
 * @version:    v1.0
 */
class Upload
{
	protected $uploadDir 	 = './upload';    //上传目录
	protected $maxSize       = 2 * 1024 *1024;//文件最大上传尺寸为200K
	protected $isRandName    = true;          //是否随机文件名
	protected $isDateDir     = true;          //是否开启日期目录
	protected $uploadInfo;					  //上传信息
	protected $newFileName;					  //上传后的文件路径
	protected $allowedSubfix = ['png','jpeg','pjpeg','jpg','bmp','wbmp','gif'];
	protected $allowedMime   = ['image/png','image/jpeg','image/wbmp','image/gif']; 

	//错误信息
	protected $errNo;					      //错误号
	protected $error;						  //错误信息

	/**
	 * [__construct 构造器]
	 * @param [array] $options [参数数组，必须是关联数组]
	 */
	public function __construct(array $options = null)
	{
		//['uploadDir'=>'images','ddd'=>1]
		if ($options) {
			// $property = get_class_vars(__CLASS__);
			foreach ($options as $key => $value) {
				// if (in_array($key, $property)) {
				if (property_exists(__CLASS__, $key)) {
					$this->$key = $value;
				}
			}
		}

		//替换路径中反斜线
		$this->uploadDir = $this->replaceSepeator($this->uploadDir);
	}

	/**
	 * [upload 文件上传]
	 * @param  [type] $key [表单中文件上传的input的name值]
	 * @return [type]      [description]
	 */
	public  function upload($key)
	{
		//1)、检查上传信息
		if (!$this->checkUploadInfo($key)) {
			return false;
		}

		// 2)、检查上传目录
		if (!$this->checkDir()) {
			return false;
		}
		// 3)、检查标准上传错误
		if (!$this->checkSystemError()) {
			return false;
		}

		// 4)、检查自定义的错误(大小、后缀、MIME)
		if (!$this->checkCustomError()) {
			return false;
		}
		
		// 5)、判断是否是上传文件
		if (!$this->checkUploadFile()) {
			return false;
		}

		// 6)、移动上传文件到指定目录
		if (!$this->checkMoveFile()) {
			return false;
		}
		//返回文件路径
		return $this->newFileName;
	}

	public function getErrorInfo()
	{
		$error = [
			-1  =>'没有文件上传信息',
			-2  =>'目录不存在',
			-3  =>'目录不可读写',
			-4  =>'文件大小超过规定',
			-5  =>'不符合规定文件后缀',
			-6  =>'不符合的mime类型',
			-7  => '不是上传文件',
			-8  =>'移动文件失败',
			 0  => '文件上传成功',
			 1  => "文件大小超出ini规定的大小",
			 2  =>"文件上传的尺寸查过表单中规定的大小",
			 3  =>"文件部分上传",
			 4  =>"文件没有被上传",
			 6  =>"找不到临时文件夹",
			 7  =>"无法写文件"
		];
		return $error[$this->errNo];
	}

	//检查上传信息
	protected function checkUploadInfo($key)
	{
		if (empty($_FILES[$key])) {
			$this->errNO = -1;
			return false;
		}
		$this->uploadInfo =$_FILES[$key];//获得文件上传信息
		return true;	
	}

	//检查上传目录
	protected function checkDir()
	{
		//检测目录是否存在
		if (!is_dir($this->uploadDir)) {
			$this->errNo = -2;
			return mkdir($this->uploadDir,0777,true);
		}

		//检测读写权限
		if (!is_readable($this->uploadDir) || !is_writable($this->uploadDir)) {
			$this->errNo = -3;
			return chmod($this->uploadDir,0777);
		}
		return true;
	}

	// 检查标准上传错误
	protected function checkSystemError()
	{
		if ($this->uploadInfo['error']) {
			$this->errNo = $this->uploadInfo['error'];
			return false;
		}
		return true;
	}

	//检查自定义的错误(大小、后缀、MIME)
	protected function checkCustomError()
	{
		//检测大小
		if ($this->uploadInfo['size'] > $this->maxSize) {
			$this->errNo = -4;
			return false;
		}
		
		//检测后缀
		$extension = $this->extname($this->uploadInfo['name']);
		if (!in_array($extension, $this->allowedSubfix)) {
			$this->errNo = -5;
			return false;
		}

		//检测mime类型
		$type = $this->uploadInfo['type'];
		if (!in_array($type, $this->allowedMime)) {
			$this->errNo = - 6;
			return false;
		}
		return true;
	}

	// 判断是否是上传文件
	protected function checkUploadFile()
	{
		//检测是否是上传文件
		if (!is_uploaded_file($this->uploadInfo['tmp_name'])) {
			$this->errNo = -7;
			return false;
		}
		return true;
	}

	//检测文件移动
	protected function checkMoveFile()
	{
		$path = $this->uploadDir;
	
		//日期路径
		if ($this->isDateDir) {
			//./upload/2017/04/10/
			$path .= date('Y/m/d') .'/';
			if (!is_dir($path)) {
				mkdir($path,0777,true);
			}	
		}

		//随机文件名
		if ($this->isRandName) {
			$path .= uniqid() .'.'. $this->extname($this->uploadInfo['name']);
		} else {
			$path .=  $this->uploadInfo['name'];
		}

		//移动文件
		if (!move_uploaded_file($this->uploadInfo['tmp_name'], $path)) {
			$this->errNo = -8;
			return false;
		}

		$this->newFileName = $path;
		$this->errNo = 0;//上传成功
		return true;
	}

	/**
	 * [extname 获取文件后缀名]
	 * @param  [type] $file [文件路径]
	 * @return [type]       [如果有后缀返回后缀名字符串，否则返回false]
	 */
	protected function extname($file)
	{
		$info = pathinfo($file);
		if (empty($info['extension'])) {
			return false;
		}
		return $info['extension'];
	}

	/**
	 * [replaceSepeator 替换路径中反斜线]
	 * @param  [type] $path [路径]
	 * @return [type]       [返回替换后的路径字符串]
	 */
	protected function replaceSepeator($path)
	{
		return rtrim(str_replace(['\\','/'],'/', $path),'/').'/';
	}
}