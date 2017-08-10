<?php 

namespace framework;
/**
 * @abstract:   文件操作类，定义文件操作的公有方法
 * @author:     yyk
 * @date:       2017-03
 * @version:    v1.0
 */
class File
{
	/**
	 * [checkDir 检查目录是否存在，不存在则创建，存在则判断是否可读可写]
	 * @param  [string] $dir [description]
	 * @return [bool]       [目录存在则返回true，创建成功返回true，
	 *         			     修改可读可写成功返回true，其他情况返回false]
	 */
	public function checkDir($dir)
	{
		if (!is_dir($dir)) {
		    return	mkdir($dir,0777,true);//递归创建子目录
		}
		if (!is_readable($dir) || !is_writable($dir)) {
			return	chmod($dir, 0755);//修改权限
		}
		return true;
	}

	/**
	 * [replaceSeparator 替换路径中的分隔符]
	 * @param  [type] $path [需要替换的]
	 * @return [type]       [替换后的路径]
	 */
	public function replaceSeparator($path)
	{
		if (is_dir($path)) {//是目录
			return rtrim(str_replace('\\', '/', $path),'/') . '/';
		}
		return str_replace('\\', '/', $path);//是文件
	}
	
	/**
	 * [extname 返回文件的扩展名]
	 * @param  [type] $file [文件路径]
	 * @return [type]       [如果是文件并且文件有扩展名则返回扩展名字符串，
	 *         				否则返回null]
	 */
	public function extname($file)
	{
		$info = pathinfo($file);
		if (empty($info['extension'])) {
			return false;
		}
		return $info['extension'];
	}

	/**
	 * [changeSize 将文件大小转换为常见的表示]
	 * @param  [type] $size [文件大小，单位字节]
	 * @return [type]       [字符串]
	 */
	public  function changeSize($size)
	{
		if ($size < 1024) {
			return $size .'B';
		} elseif ($size < 1024 ** 2) {
			return round($size / 1024) . 'K';
		} elseif ($size < 1024 ** 3) {
			return round($size /(1024**2)) .'M';
		} elseif ($size < 1024 ** 4) {
			return round($size /(1024**3)) .'G';
		} elseif ($size < 1024 ** 5) {
			return round($size /(1024**4)) .'T';
		} else {
			return $size;
		}
	}
	/**
	 * [loopDir 递归遍历]
	 * @param  [type] $dir    [目录]
	 * @param  [callable] $do [要做的操作，需要传一个函数进来]
	 * @return [void]         [无]
	 */
	public  function loopDir($dir,callable $do)
	{
		//1 判断是否是目录
		if (!is_dir($dir)) {
			return;
		}
		//2 打开目录
		$inDir = opendir($dir);
		//3 去掉.和..
		readdir($inDir);
		readdir($inDir );
		while ($data = readdir($inDir )) {
			//拼接路径
			$path = $dir . DIRECTORY_SEPARATOR . $data;
			if (is_dir($path)) {
				loopDir($path,$do);//递归调用
			} else {
				$do($data);
			}
		}
		closedir($inDir);
	}

	/**
	 * [loopDelete 递归删除目录]
	 * @param  [type] $dir [要删除的目录]
	 * @return [type]      [无]
	 */
	public  function loopDelete($dir)
	{
		//1 判断是否是目录
		if (!is_dir($dir)) {
			return;
		}
		//2 打开目录
		$inDir = opendir($dir);
		//3 去掉.和..
		readdir($inDir);
		readdir($inDir );
		while ($data = readdir($inDir )) {
			//拼接路径
			$path = $dir . DIRECTORY_SEPARATOR . $data;
			if (is_dir($path)) {
				loopDelete($path);//递归调用
			} else {
				unlink($path);//删除文件
			}
		}
		closedir($inDir);//先关闭
		rmdir($dir);     //再删除
	}
}