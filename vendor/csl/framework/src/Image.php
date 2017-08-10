<?php 
namespace framework;
include 'File.php';

/**
 * @abstract: 图片操作类  
 * @author:     
 * @date:       
 * @version:    
 */
class Image extends File
{
	protected $savedDir    = './images'; //图片保存路径
	protected $isRandName  = true;       //是否启用随机文件名
	protected $imageType   = 'png';      //默认图片类型

	public function __construct($savedDir = './images', $isRandName = true, $imageType = 'png')
	{
		
		$this->savedDir = $this->replaceSeparator($savedDir);
		if (!$this->checkDir($this->savedDir)) {
			exit('目录不存在或不可读写！');
		}
		
		$this->isRandName = $isRandName;
		$this->imageType = $this->realType($imageType);
	}

	/**
	 * [watemark 图片水印]
	 * @param  [type]  $dstFile [目标图片]
	 * @param  [type]  $srcFile [水印图片]
	 * @param  integer $pos     [位置]
	 * @param  integer $alpha   [透明度]
	 * @return [type]           [文件路径]
	 */
	public function watemark($dstFile,$srcFile,$pos=5,$alpha=100)
	{
		// 1)、路径检测
		if (!file_exists($dstFile) || !file_exists($srcFile)) {
			exit('目标图片或者水印文件不存在！');
		}
		
		// 2)、计算图片尺寸
		list($dstWidth,$dstHeight) = getimagesize($dstFile);
		list($srcWidth,$srcHeight) = getimagesize($srcFile);
		if ($srcWidth > $dstWidth || $srcHeight > $dstHeight) {
			exit('水印图片太大！');
		}

		// 3)、计算水印位置
		$position = $this->getPosition($pos,$dstWidth,$dstHeight,$srcWidth,$srcHeight);

		// 4)、合并图片
		$dstImage = $this->openImage($dstFile);
		$srcImage = $this->openImage($srcFile);
		if (!$dstImage || !$srcImage) {
			exit('文件无法打开');
		}
		imagecopymerge($dstImage, $srcImage, $position['x'], $position['y'], 0, 0, $srcWidth, $srcHeight, $alpha);

		// 5)、保存图片
		$path = $this->saveFile($dstImage,$dstFile);
		// 6)、释放资源
		imagedestroy($dstImage);
		imagedestroy($srcImage);

		return $path;
	}

	//转换图片类型
	protected  function realType($type)
	{
		$arr = ['pjpeg' =>'jpeg','jpg' =>'jpeg','bmp'=>'wbmp'];
		//判断type是否是数组的键
		if (array_key_exists($type, $arr)) {
			return $arr[$type];
		}
		return $type;
	}
	protected function getPosition($pos,$dstWidth,$dstHeight,$srcWidth,$srcHeight)
	{
		if ($pos < 1 || $pos > 9) {//随机位置
			$x = rand(0,$dstWidth - $srcWidth);
			$y = rand(0,$dstHeight - $srcHeight);
		} else {
			$x = ($pos - 1) % 3 * ($dstWidth-$srcWidth) / 2;
			$y = (int)(($pos - 1)/3) * ($dstHeight - $srcHeight) / 2;
		}
		return ['x' => $x,'y' => $y];
	}

	protected function openImage($file)
	{
		//imagecreagefromxxx
		$extension = $this->extname($file);

		if ($extension) {
			$extension = $this->realType($extension);
			$funcName = "imagecreatefrom" . $extension;
			if (function_exists($funcName)) {
				return $funcName($file);
			}
		}
		return false;
	}

	protected function saveFile($image,$orginFile)
	{
		$path = $this->savedDir;
		if ($this->isRandName) {
			$path .= uniqid();
		} else {
			$path .= pathinfo($orginFile)['filename'];
		}
		$path .= '.' . $this->imageType;

		//拼接函数名
		$funcName = 'image'.$this->imageType;
		if (function_exists($funcName)) {
			$funcName($image,$path);
			return $path;
		}
		exit('文件保存失败');
	}

	public function zoom($srcFile,$width,$height)
	{
		// 1)、路径检测
		if (!file_exists($srcFile)) {
			exit('文件无法打开');
		}
		// 2)、计算缩放尺寸
		list($srcWidth,$srcHeight) = getimagesize($srcFile);
		$size = $this->getSize($srcWidth,$srcHeight,$width,$height);

		// 3)、合并图片
		$srcImage = $this->openImage($srcFile);
		$dstImage = imagecreatetruecolor($width, $height);
		$this->mergeImage($dstImage,$srcImage,$size);

		// 4)、保存图片
		$path = $this->saveFile($dstImage,$srcFile);

		// 5)、释放资源
		imagedestroy($srcImage);
		imagedestroy($dstImage);
		return $path;
	}

	protected function mergeImage($dstImage,$srcImage,$size)
	{
		$alphaColor = imagecolortransparent($srcImage);

		if ($alphaColor < 0) {
			$alphaColor = imagecolorallocate($dstImage, 0, 0, 0);//指定黑色为透明色
		}
		var_dump($alphaColor);
		//用透明色填充背景
		imagefill($dstImage, 0, 0, $alphaColor);
		imagecolortransparent($dstImage,$alphaColor);


		imagecopyresampled($dstImage, $srcImage, $size['x'], $size['y'], 0, 0, $size['newWidth'], $size['newHeight'], $size['originWidth'], $size['originHeight']);
	}

	protected function getSize($srcWidth,$srcHeigh,$width,$height)
	{
		$size['originWidth'] = $srcWidth;
		$size['originHeight'] = $srcHeigh;

		//计算缩放比
		$widthScale = $width / $srcWidth;
		$HeighScale = $height /$srcHeigh;
		$scale = min($widthScale,$HeighScale);

		//计算大小
		$size['newWidth'] = $srcWidth * $scale;
		$size['newHeight'] = $srcHeigh * $scale;

		if ($widthScale < $HeighScale) {
			$size['x'] = 0;
			$size['y'] = ($height - $size['newHeight'])/2;
		} else {
			$size['x'] = ($width - $size['newWidth'])/2;
			$size['y'] = 0;
		}

		return $size;
	}

}

$image = new Image();
$image->zoom('images/icon.jpg',600,800);
//$image->watemark('images/likun.png','images/icon.jpg');