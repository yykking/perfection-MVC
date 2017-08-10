<?php
namespace framework;
class Page
{
	protected $total;		// 总记录数  
	protected $pageSize;	//每页的记录个数
	protected $pageCount;	//总页数
	protected $page;		//当前页
	protected $url;	        //url

	public function __construct(int $total,int $pageSize)
	{
		$this->total = $total;
		$this->pageSize = $pageSize;
		$this->pageCount = ceil($this->total / $this->pageSize);
		$this->getPage();//设定page
		$this->url = $this->getUrl(); //当前页面的url
		// echo $this->url;
	} 

	//首页
	public function first()
	{
		return $this->setUrl(1);
	}

	//上一页
	public function prev()
	{
		if ($this->page < 2) {
			$page =  1;
		} else {
			$page = $this->page - 1;
		}
		return $this->setUrl($page);
	}

	//下一页
	public function next()
	{
		if ($this->page >= $this->pageCount) {
			return $this->setUrl($this->pageCount);
		}
		return $this->setUrl($this->page + 1);
	}

	//尾页
	public function last()
	{
		return $this->setUrl($this->pageCount);
	}

	//去指定页
	public function gotoPage($page)
	{
		if ($page < 1) {
			return $this->first();
		} elseif ($page >= $this->pageCount) {
			return $this->last();
		}
		return $this->setUrl($page);
	}

	//偏移量
	public function limit()
	{
		$offset = ($this->page-1)*$this->pageSize;
		return " limit  $offset," .$this->pageSize; 
	}

	/*************************工具函数************************/  

	/**
	 * [setUrl 设置url]
	 * @param [type] $page [指定页]
	 * @return  [string] $url [返回带页数的url]
	 */
	protected function setUrl($page)
	{
		if (stripos($this->url, '?')) {
			return $this->url .'&' . 'page=' . $page;
		}  else {
			return $this->url .'?' . 'page=' . $page;
		}
	}

	//获得当前页
	protected function getPage()
	{
		 // var_dump(empty($_GET));
		//我们假定参数名固定为page
		if (empty($_GET['page'])) {
			$this->page = 1;
		} else {
			$page = (int)$_GET['page'];
			if ($page < 1) {
				$this->page = 1;
			} elseif ($page > $this->pageCount) {
				$this->page = $this->pageCount;
			} else {
				$this->page = $page;
			}		
		}
	}

	//获得当前页面的url
	public function getUrl()
	{
		$url  = $_SERVER['REQUEST_SCHEME'] .'://';//协议
		$url .= $_SERVER['SERVER_NAME'];          //主机
		$url .=  ':' . $_SERVER['SERVER_PORT'];          //端口
		$data = parse_url($_SERVER['REQUEST_URI']);//获得路径和参数
		$url .= $data['path'];//拼接路径
		if (!empty($data['query'])) {//参数不为空
			parse_str($data['query'],$query);//query是参数数组
			unset($query['page']);//干掉page参数
			$url .= '?' . http_build_query($query);
		}
		$url = rtrim($url,'?');
		return $url;
	}
}