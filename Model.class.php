<?php

include './DBConfig.php';
	class Model
	{
		//常量写在配置文件中
		private $dbname = DBNAME;        //选择的数据库
		private $host = HOST;            //数据库地址
		private $user = USER;            //用户名
		private $password = PASSWORD;    //密码
		private $charset = CHARSET;      //字符集
		private $prefix = PREFIX;        //表前缀
		private $link = null;
		//操作的数据表
		private $tabName;
		//SQL语句，方便定位错误
		private $sql;

		public function __construct($tabName='')
		{
			//判断用户是否传入表名，如果是直接使用该类，是必须传入表名的
			if(!empty($tabName)){
				$this->tabName = $tabName;
			}else{
				//没传入表名，使用继承该类的子类的类名作为表名
				$className = get_class($this);  //一定是在子类中执行这行才有意义
				$this->tabName = strtolower(substr($className,0,-5)); //使用rtrim删除类名中 Model 也可以 或 substr截取类名
			}
			$this->link = $this->connect();
		}

		//连接数据库
		private function connect()
		{
			$link = @mysqli_connect($this->host,$this->user,$this->password,$this->dbname);
			//判断链接是否成功
			if(!$link){
				return '链接数据库失败';
			}
			mysqli_set_charset($link,$this->charset);
			return $link;
		}

		//

		public function insert($arr)
		{
			//准备接收键值的变量
			$key = '';
			$value = '';
			//遍历用户传进来的数据
			foreach($arr as $k=>$v){
				$key .= '`'.$k.'`,';
				$value .= '"'.$v.'",';
			}
			//删除多余的逗号
			$key = rtrim($key,',');
			$value = rtrim($value,',');
			//拼接SQL语句
			$sql = 'INSERT INTO '.$this->tabName.' ('.$key.') VALUES ('.$value.')' ;
			echo $sql;
		}

		//关闭数据库
		private function __destruct()
		{
			if($this->link !== null){
				mysqli_close($this->link);
			}
		}
	} 

	// $model = new Model('stu');
	// $model->insert(['name'=>'lisi','age'=>20]);