<?php 
namespace framework;
class Model
{
	protected $link;				//数据库连接
	protected $host;				//主机地址
	protected $user;				//用户名
	protected $pwd;					//密码
	protected $dbName;				//数据库名				
	protected $charset;				//字符集
	protected $prefix;				//表前缀
	protected $table ;
	protected $sql;					//最后执行sql语句
	protected $options ;			//参数数组
	protected $cacheFields;         //字段缓存
	protected $cacheDir;     		//缓存目录
	protected $stateFunc    = ['max','min','avg','sum','count'];

	public function __construct(array $config)
	{
		$this->host 	= $config['DB_HOST'];
		$this->user 	= $config['DB_USER'];
		$this->pwd 		= $config['DB_PWD'];
		$this->dbName 	= $config['DB_NAME'];
		$this->charset 	= $config['DB_CHARSET'];
		$this->prefix 	= $config['DB_PREFIX'];

		$this->cacheDir = $this->checkDir($config['DB_CACHE']);

		//连接数据库
		$this->link = $this->connect();
		$this->table = strtolower($this->getTable());  //设置表名
		$this->cacheFields = $this->getCacheFields();//初始化字段
		$this->options = $this->initOptions();//初始化参数数组
	}

	public function __destruct()
	{
		mysqli_close($this->link);
	}

	public function getErrorNo()
	{
		return mysqli_errno($this->link);
	}

	public function getErrorInfo()
	{
		return mysqli_error($this->link);
	}

	//通过魔术方法查询sql语句
	public function __get($name)
	{
		if ('sql' == $name) {
			return $this->sql;
		}
	}

	//重写魔术方法
	public function __call($name,$args)
	{
		$funcName = strtolower($name);
		$subName = substr($funcName, 0,5);
		if (in_array($funcName, $this->stateFunc)) {//如果是统计方法
			return $this->tongji($funcName,$args);
		} elseif ( 'getby' == $subName) {//如果是getby
			$field = substr($funcName, 5);
			return $this->getby($field,$args);
		}
	}
	//getFieldsByxxx($value,array $fields)
	/**
	 * [getby 根据指定字段获得记录]
	 * @param  [type] $field [指定的字段]
	 * @param  [type] $args  [值]
	 * @return [type]        [记录集]
	 */
	public function getby($field,$args)
	{
		//$this->options['fields'] = $field;
		if (count($args)>0) {//有参数
			if (is_string($args[0])) {
				$this->options['where'] = 'where '. $field .'='."'{$args[0]}'";
			} else {
				$this->options['where'] = 'where '. $field .'='.$args[0];
			}	
		} 
		return $this->select(MYSQLI_ASSOC);
	}


	/**
	 * [tongji 执行统计查询]
	 * @param  [type] $funcName [统计的方法名]
	 * @param  [type] $args     [参数]
	 * @return [type]           [结果为二维数组]
	 */
	public function tongji($funcName,$args)
	{
		$funcName = strtolower($funcName);
		if (in_array($funcName, $this->stateFunc)) {
			if (empty($args)) {
				$para = '*';
			} else {
				$para = $args[0];
			}

			$this->options['fields'] = $funcName . "($para)";

				//查询
			$result = $this->select(MYSQLI_NUM);
			if ($result && count($result)>0) {
				return $result;
			}
			
		}
	}
	
	public function where($where)
	{
		$this->condition('where',$where,true);
		return $this;
	}

	public function order($order)
	{
		$this->condition('order',$order);
		return $this;
	}

	public function group($group)
	{
		$this->condition('group',$group);
		return $this;
	}
	public function having($having)
	{
		$this->condition('having',$having,true);
		return $this;
	}
	public function limit($limit)
	{
		$this->condition('limit',$limit);
		return $this;
	}

	public function fields($fields)
	{
		$this->condition('fields',$fields);
		return $this;
	}

	
	
	/**
	 * [select 查询]
	 * @param  [type] $resultType [返回结果集类型]//MYSQLI_ASSOC, MYSQLI_NUM, or MYSQLI_BOTH
	 * @return [type]             [二维数组]
	 */
	public function select($resultType = MYSQLI_BOTH)
	{
		//"select uid,username from bbs_user where uid<100 group by uid having uid>0 order by uid limit 5";
		$sql = "SELECT %FIELD% FROM %table% %WHERE% %GROUP% %HAVING% %ORDER% %LIMIT%";

		$sql = str_replace(
			              [
			              	'%FIELD%',
			              	'%table%',
			              	'%WHERE%',
			              	'%GROUP%',
			              	'%HAVING%',
			              	'%ORDER%',
			              	'%LIMIT%'
			              ], 
			              [
			              	$this->options['fields'],
			              	$this->table,
			              	$this->options['where'],
			              	$this->options['group'],
			              	$this->options['having'],
			              	$this->options['order'],
			              	$this->options['limit']
			              ], 
			              $sql
			              );	

		return $this->query($sql,$resultType);
	}


	/**
	 * [insert 数据插入]
	 * @param  array  $data [键值对的关联数组，键必须是表的字段]
	 * @param [type] $[isInsertId] [是否返回插入记录的主键值]
	 * @return [type]       [插入成功返回true或id值]
	 */
	public function insert(array $data,$isInsertId=false)
	{
		$sql = "INSERT INTO %TABLE%(%FIELDS%) VALUES(%VALUES%)";

		//1 过滤无效字段
		$data = $this->validField($data);
		//2 给字符串类型数据增加单引号
		$data = $this->addQuote($data);

		$this->options['fields'] = join(',',array_keys($data));
		$this->options['values'] = join(',',$data);
		$sql = str_replace(
			              [
			              	'%FIELDS%',
			              	'%TABLE%',
			              	'%VALUES%',
			              ], 
			              [
			              	$this->options['fields'],
			              	$this->table,
			              	$this->options['values'],
			              ], 
			              $sql
			              );
		return $this->exec($sql,$isInsertId);
	}

	
	
	//$obj->order('grade')->limit('2')->delete()
	public function  delete()
	{
		if (empty($this->options['where'])) {
			exit('请添加删除条件！');
		}

		$sql = "DELETE FROM %TABLE%  %WHERE%  %ORDER%  %LIMIT%";
		$sql = str_replace(
			              [
			              	'%WHERE%',
			              	'%TABLE%',
			              	'%ORDER%',
			              	'%LIMIT%'
			              ], 
			              [
			              	$this->options['where'],
			              	$this->table,
			              	$this->options['order'],
			              	$this->options['limit'],
			              ], 
			              $sql
			              );
		return $this->exec($sql,false);
	}

	public function update(array $data)
	{
		if (empty($this->options['where'])) {
			exit('请添加更新条件！');
		}
		$sql = "UPDATE %TABLE% SET %SET% %WHERE%  %ORDER%  %LIMIT%";

		//1 过滤无效字段
		$data = $this->validField($data);
		//2 给字符串类型数据增加单引号
		$data = $this->addQuote($data);
		//['uid'=>9,'username'=>'dengke']
		//uid=9,username='dengke'
		//将数组转换为字符串赋值给options['set']
		$this->options['set'] = $this->array2string($data);

		$sql = str_replace(
			              [
			              	'%SET%',
			              	'%WHERE%',
			              	'%TABLE%',
			              	'%ORDER%',
			              	'%LIMIT%'
			              ], 
			              [
			             	$this->options['set'],
			              	$this->options['where'],
			              	$this->table,
			              	$this->options['order'],
			              	$this->options['limit'],
			              ], 
			              $sql
			              );
		return $this->exec($sql,false);
	}

	protected function getTable()
	{
		//\index\model\UserModel   index\model\CategoryModel
		if (!empty($this->table)) {//有默认值
			return $this->prefix . $this->table;
		}
		
		$className = explode('\\', get_class($this));//获得当前类名
		$className = array_pop($className); //取数组最后一个元素
		if (stripos($className, 'model') === false) {
			return $this->prefix . $className;
		}
		return $this->prefix . substr($className,0,-5);
	}
	protected function connect()
	{
		$link = mysqli_connect($this->host,$this->user,$this->pwd);
		if (!$link) {
			exit('数据库连接失败！');
		}
		if (!mysqli_select_db($link,$this->dbName)) {
			exit('选择数据库失败');
		}
		if (!mysqli_set_charset($link,$this->charset)) {
			exit('设置字符集失败');
		}
		return $link;
	}

	protected function condition($key ,$value,$and = false)
	{
		$keys = ['where'=>'where ','group'=>'group by ','having' =>'having ',
				  'order' => 'order by ','limit'=>'limit ','fields'=>' '
				];
		$sepeator = $and ? ' and ' : ',';
		if (!empty($value)) {
			if (is_string($value)) {
				$this->options[$key] = $keys[$key] . $value;
			} elseif (is_array($value)) {
				$this->options[$key] = $keys[$key] .join($sepeator,$value);
			}
		}
	}


	//执行查询sql语句
	protected function query($sql,$resultType)
	{
		$this->sql = $sql; //保留sql语句
		$this->options = $this->initOptions();//初始化options数组
		$result = mysqli_query($this->link,$sql);
		if ($result) {
			return mysqli_fetch_all($result,$resultType);
		}
		return false;
	}

	/**
	 * [addQuote 给字符串类型的数据添加单引号]
	 * @param [type] $data [数组]
	 */
	protected function addQuote($data)
	{
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				if (is_string($value)) {
					$data[$key] = "'$value'";
				}
			}
		}
		return $data;
	}
	
	//过滤无效字段
	protected function validField($data)
	{
		$fields = array_flip($this->cacheFields);
		$data = array_intersect_key($data, $fields);
		return $data;
	}
	//执行增、删、改sql语句
	protected function exec($sql,$isInsertId)
	{
		$this->sql = $sql;
		$this->options = $this->initOptions();

		$result = mysqli_query($this->link,$sql);
		if ($result && $isInsertId) {
			return mysqli_insert_id($this->link);//返回插入记录的主键值
		}
		return $result;
	}

	/**
	 * [array2string 将关联数组转换：“键1=值1，键2=值2...”]
	 * @param  array  $data [数组]
	 * @return [type]       [字符串]
	 */
	protected function array2string(array $data)
	{
		if (!empty($data)) {
			$str = '';
			foreach ($data as $key => $value) {
				$str .= $key .'=' .$value .',';
			}
			$str = rtrim($str,',');
			return $str;
		}
	}	

	protected function checkDir($dir)
	{
		$dir = rtrim(str_replace('\\', '/', $dir),'/').'/';
		$flag = true;
		if (!is_dir($dir)) {
			$flag = mkdir($dir,0777,true);
		} elseif (!is_readable($dir) || !is_writeable($dir)) {
			$flag = chmod($dir, 0777);
		}
		if ($flag) {
			return $dir;
		} else {
			exit('无法生成缓存目录');
		}
	}

	//获得缓存字段
	protected function getCacheFields()
	{
		$path = $this->table . '.php';
		$cachePath = $this->cacheDir . $path;//拼接缓存文件路径

		//1 判断是否有字段缓存文件
		if (file_exists($cachePath)) {
			return include($cachePath);
		}

		//2 没有缓存文件,查询表字段
		$sql = 'desc '.$this->table;
		$result = $this->query($sql,MYSQLI_ASSOC);

		//3 生成缓存文件
		if ($result) {
			//获得表的字段
			foreach ($result as $key => $value) {
				if ($value['Key'] == 'PRI') {
					$fields['_PK'] = $value['Field'];
				}
				$fields[] = $value['Field'];
			}
			$this->cacheFields = $fields;//保留缓存字段

			//构建缓存文件内容（php文件）
			$str = "<?php \n return " . var_export($fields,true) .';';
			
			file_put_contents($cachePath, $str);//写文件
		}
	}

	/**
	 * [initOptions 初始化参数数组]
	 * @return [type] [参数数组]
	 */
	protected function initOptions()
	{
		//将表的字段转换为以逗号分隔的字符串
		$field = join(',',array_unique($this->cacheFields));
		return [
			'fields'     =>$field,
			'table'      =>$this->table,
			'where'      =>'',
			'group'      =>'',
			'having'     =>'',
			'order'      =>'',
			'limit'      =>''
		];
	}

}
