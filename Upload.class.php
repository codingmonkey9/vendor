<?php
	//能够实现多文件上传,也可以单文件上传
	class Upload
	{	
		//文件上传表单的name属性值
		private $fileName;
		//文件上传后，创建的保存路径
		private $path;
		//文件的类型
		private $type;
		//文件大小
		private $size;
		//用于接收遍历后的文件
		private $files = [];

		//初始化  注意：如果用户传的路径是嵌套的，需要给mkdir()传第二个和第三个参数
		public function __construct($fileName,$path='./statics/images',$size=1500000,array $type=['image/jpeg','image/png','image/jpg','image/gif'])
		{
			$this->fileName = $fileName;
			$this->path = $path;
			$this->type = $type;
			$this->size = $size;
		}

		//为避免可能出现用户上传的是单文件却调用多文件处理方法的错误，新进行判断用户上传的文件是不是三维数组is_array($_FILES['pic']['name'])
		public function uploadFile()
		{
			//这个判断也可可以写在uploads 和 upload 方法里判断
			if(is_array($_FILES['pic']['name'])){
				echo $this->uploads();  //输出信息
			}else{
				echo $this->upload();  
			}
		}

		//用户上传多文件时调用此方法。
		public function uploads()
		{
			//其实多文件上传时，就是遍历一遍，然后在每次遍历的时候，把error，type，size都判断一次
			//每遍历一次就把所有单文件上传的步骤执行一次
			foreach($_FILES[$this->fileName]['name'] as $k=>$v){
				$this->files['name'] = $v;
				$this->files['type'] = $_FILES[$this->fileName]['type'][$k];
				$this->files['tmp_name'] = $_FILES[$this->fileName]['tmp_name'][$k];
				$this->files['error'] = $_FILES[$this->fileName]['error'][$k];
				$this->files['size'] = $_FILES[$this->fileName]['size'][$k];
				//把那些方法都调用一遍,判断必须判断类型，否则返回的错误信息也会转换成真
				if($this->fileError()!==true){
					echo $this->fileError();      //输出返回的错误信息
				}elseif($this->fileType()!==true){
					echo $this->fileType();
				}elseif($this->fileSize()!==true){
					echo $this->fileSize();
				}else{
					echo $this->moveImg();
				}
			}
		}

		//当用户是单文件上传时，调用此方法
		public function upload()
		{
			//单文件上传把所有步骤都写在这一个方法里
			$file = $_FILES[$this->fileName];
			//判断错误号
			if($file['error']>0){
				switch($file['error']){
					case 1:
					case 2:
						return '文件过大';
					case 3:
						return '上传文件不完整，请重新上传';
					case 4:
						return '请选择文件';
					case 6:
						return 'tmp不存在';
					case 7:
						return '没有权限';
				}
			}
			//判断文件类型
			if(!in_array($file['type'],$this->type)){
				return '上传文件类型不合法';
			}
			//判断文件大小
			if($file['size']>$this->size){
				return '上传文件过大，请压缩后上传';
			}
			//判断保存文件的目录是否存在
			if(!file_exists($this->path)){
				mkdir($this->path,0777,true);  //可以递归创建目录
			}
			//过滤用户可能传进的/ \
			$this->path = rtrim($this->path,'/\\');
			//获取文件后缀
			$suffix = strrchr($file['name'],'.');
			do{
				$newPath = $this->path.'/'.md5(time().mt_rand(100,999).uniqid()).$suffix;
			}while(file_exists($newPath));
			if(move_uploaded_file($file['tmp_name'],$newPath)){
				return '<font color="green">上传文件成功</font>';
			}else{
				return '<font color="red">上传文件失败</font>';
			}
		}

		//判断错误号
		private function fileError()
		{
			if($this->files['error']>0){
				switch($this->files['error']){
					case 1: return '上传的文件超过了 php.ini 中 upload_max_filesize选项限制的值';
					case 2: return '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值';
					case 3: return '文件只有部分被上传';
					case 4: return '没有文件被上传';
					case 6: return '找不到临时文件夹';
					case 7: return '文件写入失败';
				}
			}
			return true;
		}

		//判断文件类型是否合法
		private function fileType()
		{
			if(!in_array($this->files['type'],$this->type)){
				return '上传类型不合法';
			}
			return true;
		}

		//判断上传文件大小是否合法
		private function fileSize()
		{
			if($this->files['size']>$this->size){
				return '上传文件过大，请压缩后上传';
			}
			return true;
		}

		//从临时目录中移出
		private function moveImg()
		{
			//判断保存文件的目录是否存在
			if(!file_exists($this->path)){
				mkdir($this->path,0777,true);
			}
			//用户传路径的时候可能会多/ \  过滤掉
			$this->path = rtrim($this->path,'/\\');
			//获取文件后缀
			$suffix = strrchr($this->files['name'],'.'); 
			//重命名文件
			do{
				$newPath = $this->path.'/'.md5(time().mt_rand(100,999).uniqid()).$suffix;
			}while(file_exists($newPath));
			//移动文件
			if(move_uploaded_file($this->files['tmp_name'],$newPath)){
				return '<font color="green">上传文件成功</font>';
			}else{
				return '<font color="red">上传文件失败</font>';
			}
		}
	}