<?php
namespace framework;
class VerifyCode
{
	public $width     = 100;     //画布宽
	public $height    = 30;      //画布高
	public $type      = 3;       //验证码类型：1数字 2 字母3 混合
	public $len       = 4;       //验证码长度
	public $imageType = 'png';   //图片类型
	public $code;                //验证码字符串
	public $canvas;              //画布

	/****************构造和析构***********************/
    public function __construct($width=100,$height=30,int $type=3,int $len=4,$imageType='png')
    {
    	$this->width = $width < 0 ? $this->width:$width;
    	$this->height = $height < 0?$this->height : $height;
    	$this->type = ($type<1 || $type>3)?$this->type : $type;
    	$this->len = ($len < 4 || $len > 10)?$this->len : $len;
    	$this->imageType = $this->getImageType($imageType); 
    }

    /************对外接口**********************/
    public static function yzm($width=100,$height=30,int $type=3,int $len=4,$imageType='png')
    {
    	$vc = new self($width,$height,$type,$len,$imageType);
    	$vc->outputImage();
    	return $vc->getCode();
    }

    //输出验证码
    public function outputImage()
    {
    	// 1)、创建画布
    	$this->createCanvas();
		// 2)、生成验证码字符串
		$this->generateCode();
		// 3)、将验证码字符串画到画布上
		$this->drawCode();
		// 4)、画干扰元素
		$this->drawDisturb();
		// 5)、发送验证码
		$this->displayCode();
		// 6)、释放资源
		$this->destroyImage();
    }
    //获取验证码字符串
    public function getCode()
    {
    	return $this->code;
    }

    /************画验证码的步骤*******************/
    // 1)、创建画布
    protected function createCanvas()
    {
    	$this->canvas = imagecreatetruecolor($this->width, $this->height);
    	$color = $this->randColor(0,127);
    	imagefill($this->canvas, 0, 0, $color);
    }
    protected function generateCode()
    {
    	$funcName = [0,'randDigital','randAlpha','randAlphaDigital'];
    	$func = $funcName[$this->type];//获得方法名
    	$this->$func();//
    }

    protected function drawCode()
    {
    	$len = strlen($this->code);
    	for ($i=0; $i < $len ; $i++) { 
    		$x = $i * ($this->width / $this->len) + 5 ;
    		$y = rand(1,$this->height-15);
    		$color = $this->randColor(150,230);
    		imagechar($this->canvas, 5, $x, $y, $this->code[$i], $color);
    	}
    }
    //画干扰线
    protected function drawDisturb()
    {
    	for ($i=0; $i < 200; $i++) { 
    		$x = rand(1,$this->width);
    		$y = rand(1,$this->height);
    		imagesetpixel($this->canvas, $x, $y, $this->randColor(127,180));
    	}
    }

    protected function displayCode()
    {
    	header("content-Type:image/" . $this->imageType);
    	//imagejpeg imagepng
    	$func = 'image' . $this->imageType;
    	if (function_exists($func)) {//如果函数存在
    		$func($this->canvas);
    	} else {
    		exit("不支持该类型！<br/>");
    	}
    	
    }
    
    protected function destroyImage()
    {
    	imagedestroy($this->canvas);
    }

    /***************辅助方法/工具方法*******************/
    protected function getImageType($type)
    {
    	$arr = ['pjpeg' => 'jpeg','jpg' =>'jpeg','bmp' => 'wbmp'];
    	if (array_key_exists($type, $arr)) {
    		$type = $arr[$type];
    	}
    	return $type;
    }

    //随机颜色
    protected function randColor($low,$hight)
    {
    	return imagecolorallocate($this->canvas, rand($low,$hight), rand($low,$hight), rand($low,$hight));
    }

    /***********验证码字符串生成******/
    protected function randDigital()
    {
    	$str = '1234567890';
    	$this->code = substr(str_shuffle($str), 0,$this->len);
    }
	 protected function randAlpha()
    {
    	$str = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';
    	$this->code = substr(str_shuffle($str), 0,$this->len);
    }
    protected function randAlphaDigital()
    {
    	$str = '1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';
    	$this->code = substr(str_shuffle($str), 0,$this->len);
    }
}