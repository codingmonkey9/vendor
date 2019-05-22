<?php
class Model{
	//成员属性
	protected $host;      //地址 
	protected $pwd;       //密码
	protected $user;      //用户名
	protected $tabname;   //表名
	protected $prefix;    //表前缀
	protected $dbname;    //数据库名
	protected $charset;   //字符集
	protected $link=null; //连接数据库返回的资源
	protected $sql;       //sql语句
	public $cache;        //查询字段缓存文件
   private $where;
   private $limit;
   private $order;
   private $field;
   private $method=array('limit','order','where','field');

   // 成员方法
   // 1.获取表名和初始化属性
   function __construct($tabname='',$cache='./cache/'){
	   	//初始化成员属性
	   	$this->host=DB_HOST;
	   	$this->pwd=DB_PWD;
	   	$this->user=DB_USER;
	   	$this->charset=CHARSET;
	   	$this->prefix=DB_PREFIX;
	   	$this->dbname=DB_NAME;
	   	$this->cache=rtrim($cache,'/').'/';
	   //	var_dump($tabname);
	   	//获取数据库中的表名
	   	if($tabname==''){
	   		$this->tabname=$this->prefix.strtolower(substr(get_class($this),0,-5));
	   	}else{
	   		//var_dump($tabname);
	   		$this->tabname=$this->prefix.$tabname;
	   		//var_dump($this->tabname);
	   		//var_dump($tabname);
	   		//var_dump($this->prefix);
	   	}
	   	$this->link=$this->connect();
   }
     //2.链接数据库
   private function connect(){
   	$link=@mysqli_connect($this->host,$this->user,$this->pwd,$this->dbname);
   	if(!$link){
   		return '连接数据库失败';
   	}
   	mysqli_set_charset($link,$this->charset); //   什么时候用$this呢  我想这里用$this 行不行呢？？？？
   	return $link;
   }

   	//6.获取数据库合法字段
   private function getField(){
   	$pathinfo=$this->cache.$this->tabname.'Cache.php';
      //var_dump($pathinfo);
   	if(file_exists($pathinfo)){
   		return include $pathinfo;
         //合法字段存储在文件中
   //当文件存在时，每次都直接查询文件即可，只有当文件不存在时，才去查询数据库中的合法字段*************
   	}else{
   		$sql=" DESC ".$this->tabname;
   		//echo 'sql:'. $sql;
   		$result=$this->query($sql);
   		return $this->writeField($result);

   	}
   }

   	//7.准备查询的SQL语句
   	private function query($sql){
   		$this->clearWhere();
   		//var_dump($sql);
   		$this->sql=$sql;
   		$rows=array();
   		$result=mysqli_query($this->link,$sql);
   		//var_dump($result);
   		//echo 111;
   	//  $result = mysqli_query($this->link,$sql);
   		if($result && mysqli_num_rows($result)>0){
   			while($row=mysqli_fetch_assoc($result)){
   				$rows[]=$row;
   			}
   			return $rows;
   			//echo 1122;
   		}else{
   			return false;
   		}
   	}

   	//8.
   	private function writeField(array $data){
   		if(!file_exists($this->cache)){
   			mkdir($this->cache);
   		}
   		$pathinfo=$this->cache.$this->tabname.'Cache.php';
   		$fields=array();
   		foreach($data as $key=>$val){
   			if($val['Key']=='PRI'){
   				$fields['_pk']=$val['Field'];

   			}
   			if($val['Extra']=='auto_increment'){
   				$fields['auto']=$val['Field'];
   			}
   			$fields[]=$val['Field'];

   		}
   		file_put_contents($pathinfo,"<?php \r\n return ".var_export($fields,true)."\r\n?>");
   		return $fields;
   	}

   	//3.准备添加数据的SQL语句
   	public function insert(array $data){
   		$key='';
   		$val='';
   		$field=$this->getField();
   		foreach($data as $k=>$v){
   			if(in_array($k,$field)){
   				$key.='`'.$k.'`,';
   				$val.="'".$v."',";
   			}
   		}
   		$key=rtrim($key,',');
   		$val=rtrim($val,',');
   		$sql="INSERT INTO {$this->tabname}({$key}) VALUES($val)";
   		return $this->exec($sql);
   	}

   	//4.发送SQL语句
   	private function exec($sql){
   		$this->sql=$sql;
   		$result=mysqli_query($this->link,$sql);
   		//var_dump($result);
   		if($result && mysqli_affected_rows($this->link)>0){
   			return mysqli_insert_id($this->link)?mysqli_insert_id($this->link):mysqli_affected_rows($this->link);
   		}else{
   			return false;
   		}
   	}

   	//5.使用魔术方法查看SQL语句
   	function __get($pro){
   		if($pro=='sql'){
   			echo $sql;   
   		//	echo $this->sql;          
   		}
   		else{
   			echo '不存在';
   		}
   	}

      //9.删除
      public function delete(){
        // var_dump($this->where);
         if(!empty($this->where)){
            $where=" WHERE ".$this->where;
            //echo 222;
         }else{
            $where='';
            if(!empty($_GET)){
               $field=$this->getField();
               $id=$field['_pk'];
               foreach($_GET as $k=>$v){
                  if($id=$k){
                     $val=$v;
                  }
               }
               //echo 222;
               $where=' WHERE '.$id.'='.$val;
            }
         }
         $sql="DELETE FROM {$this->tabname} {$where}";
        // echo $sql;
         return $this->exec($sql);
      }

      //10.where是变化的，不能定义固定的，用__call调用不存在的属性
      function __call($methodname,$args){
         if(in_array($methodname,$this->method)){
            if($methodname=='where'){
               $this->where=isset($args['0'])?$args['0']:'';
               //var_dump($args[0]);
            }elseif($methodname=='limit'){
               if(count($args)>1){
                  $this->limit=$args[0].','.$args[1];
               }
            }elseif($methodname=='field'){
               $this->field=$args;
            }elseif($methodname=='order'){
               $this->order=$args[0];
            }
         }
         //var_dump($this->where);
         return $this;
      }

      //qingkong 
      private function clearwhere(){
         $this->limit='';
         $this->where='';
         $this->order='';
         $this->field='';
      }

      //11.update
      public function update(array $data){
         $field=$this->getField();
         $update='';
         foreach($data as $k=>$v){
            if(in_array($k,$field) && $k!=$field['_pk']){
               $update.='`'.$k.'`="'.$v.'",';
            }elseif($k==$field['_pk']){
               $con='`'.$k.'`="'.$v.'"';
            }
         }
         $update=rtrim($update,',');
         if(!empty($this->where)){
            $where=" WHERE ".$this->where;
         }else{
            $where=" WHERE ".$con;
         }
         $sql="UPDATE {$this->tabname} SET {$update} {$where}";
         //echo $sql;
         return $this->exec($sql);
      }
    
      //12..select
      public function select(){
         $limit=$where=$order='';
         if(!empty($this->limit)){
            $limit=" LIMIT ".$this->limit;
         }
         if(!empty($this->where)){
            $where=" WHERE ".$this->where;
         }
         if(!empty($this->order)){
            $order=" ORDER BY ".$this->order;
         }
         if(!empty($this->field)){
            $field=$this->getField();
            $hefa=array_intersect($this->field,$field);
            $fields=implode(',',$hefa);
         }else{
            $fields=" * ";
         }
         $sql="SELECT {$fields} FROM {$this->tabname} {$where} {$order} {$limit}";
         echo $sql;
         return $this->query($sql);
      }

      //13.total
      public function total(){
         $where='';
         if(!empty($this->where)){
            $where=" WHERE ".$this->where;
         }
         $field=$this->getField();
         if(isset($field['_pk'])){
            $fields=$field['_pk'];
         }else{
            $fields="*";
         }
         $sql="SELECT COUNT({$fields}) as total FROM {$this->tabname } {$where}";
         return intval($this->query($sql)[0]['total']);
      }

      //14.查询单条数据
      public function find(){
         $where='';
         if(!empty($this->where)){
            $where=" WHERE ".$this->where;
         }
         if(!empty($this->field)){
            $field=$this->getField();
            $hefa=array_intersect($this->field,$field);
            $fields=implode(',',$hefa);
         }else{
            $fields="*";
         }
         $sql="SELECT {$fields} FROM {$this->tabname} {$where} LIMIT 1";   
         return $this->query($sql)[0];
      }

   //最后.关闭数据库
   function __destruct(){
   	if($this->link!=null){
   		mysqli_close($this->link);
   	}
   }
}