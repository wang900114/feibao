<?php
use Think\Controller;
use Org\Util\Dir;
use Think\Upload;
// 文件模块
class FileController extends Controller {
	//文件列表
	public function index(){
		if(IS_SAE){
			$this->sae=1;
		}else{
			$this->sae=0;
		}	

		//路径构造
		if((!$_GET['path'] && !$_GET['up'] && !$_SESSION['path'])||$_GET['root']) $_SESSION['path'] = $_SERVER['DOCUMENT_ROOT'];
		if($_GET['path']) $_SESSION['path'] = $_SESSION['path'].'/'.$_GET['path'];
		$_SESSION['path'] = str_replace('//', '/', $_SESSION['path']);
		$path = $_SESSION['path'];
		
		//返回上层目录		
		if($_GET['up']){
			//限制在网站根目录
			if(strlen($path)>strlen($_SERVER['DOCUMENT_ROOT'])){
				preg_match('/^.*\//', $path, $match);
				$path = substr($match[0],0,-1);
				$_SESSION['path'] = $path; 
			}else{
				$_SESSION['path'] = $_SERVER['DOCUMENT_ROOT'];
			}
		} 		
		$dir = new Dir($path);
		$list = $dir->_values;
		foreach ($list as $key => $val){
			$tmp=array();
			$tmp=pathinfo($list[$key]['filename']);
			if($tmp['extension'] == 'ini' || $tmp['extension'] == 'bat'){//隐藏文件
				unset($list[$key]);
			}else{
				$list[$key]['filename'] = iconv('gbk', 'utf-8', $list[$key]['filename']);
				$list[$key]['fileimg'] = $this->getFileImg($val);
			}
		}
		$this->assign('list',$list);
		$this->display();
	}
	//文件图标
	public function getFileImg($ary){
		if(key_exists('type', $ary)){			
			if($ary['type']=='dir'){
				$filename = 'dir';
			}else if($ary['type']=='file'){
				switch ($ary['ext']){
					case 'dir':
						$filename = 'dir';
						break;
					case 'php':
						$filename = 'php';
						break;
					case 'jpg':
						$filename = 'jpg';
						break;
					case 'gif':
						$filename = 'gif';
						break;
					case 'png':
						$filename = 'image';
						break;
					case 'js':
						$filename = 'js';
						break;
					case 'flash':
						$filename = 'flash';
						break;
					case 'css':
						$filename = 'css';
						break;
					case 'txt':
						$filename = 'txt';
						break;
					case 'zip':
						$filename = 'zip';
						break;
					case 'html':
						$filename = 'htm';
						break;
					case 'htm':
						$filename = 'htm';
						break;
					case 'wmv':
						$filename = 'wmv';
						break;
					case 'rm':
						$filename = 'rm';
						break;
					case 'mp3':
						$filename = 'mp3';
						break;						
					default:
						$filename = 'unknow';			
				}
			}
			$fileimg = '<img src="'.__ROOT__.'/Public/Images/file/'.$filename.'.gif" align="absmiddle" />';
			return $fileimg;
		}
	}
	//文件编辑
	public function edit(){
		if($_POST['content']){
			$path = $_SESSION['path'].'/'.$_POST['filename'];
			$data = $_POST['content'];
			file_put_contents($path, $data);
			$this->DwzCallback('编辑成功！',null,200,null,'closeCurrent');			
		}else{
			$filename = $_GET['filename'];
			$path = $_SESSION['path'].'/'.$filename;
			$content = file_get_contents($path);
			mb_detect_encoding($content);
			if(mb_detect_encoding($content)!='UTF-8') $content = iconv("gbk", "utf-8", $content);
			$this->assign('content',$content);
			$this->display();
		}
	}
	//文件重命名
	public function rename(){
			$this->assign('filename',$_GET['filename']);
			$this->display();
		
	}

	//保存上传文件
	public function saveFiles(){
			$path = $_SESSION['path'];
			if(rename($path.'/'.$_POST['oldname'], $path.'/'.$_POST['newname'])){
			 	$this->DwzCallback('文件重命名成功！');
			}else{
				$this->DwzCallback('文件重命名失败！');
			} 
	}
	//文件上传
	public function upload(){		
		if(isset($_FILES['file']['name'])){
			$upload = new \Think\Upload(); //实例化
			$upload->maxSize	= 1048576 * 3;
			$upload->exts 		= array('jpg', 'gif', 'png', 'jpeg', 'txt', 'md', 'html', 'htm');// 设置附件上传类型
			$upload->rootPath	= './Uploads/'; // 设置附件上传根目录
			$upload->savePath	= null; // 设置附件上传（子）目录
			if(!$upload->upload()) { 
				$this->DwzCallback($upload->getError(),null,300,null,'closeCurrent');
			}else{
				$this->DwzCallback('上传成功！',null,200,null,'closeCurrent');
			}
		}else{
			$this->display();
		}
	}
	public function aliupload() {
		if(isset($_FILES['file']['name'])){
			$config = array(
			    'maxSize'    =>    3145728,
			    'savePath'   =>    'Uploads/',
			    'saveName'   =>    array('uniqid',''),
			    //'exts'       =    array('jpg', 'gif', 'png', 'jpeg'),
			    //'autoSub'    =    true,
			    'subName'    =>    ''//array('date','Ymd'),
			);
			$upload = new \Think\Upload($config,'Alioss'); //实例化
			if(!$upload->upload()) { 
				$this->DwzCallback($upload->getError(),null,300,null,'closeCurrent');
			}else{
				$this->DwzCallback('上传成功！',null,200,null,'closeCurrent');
			}
		}else{
			$this->display();
		}
	}
	//文件删除，不支持删除非空文件夹
	public function foreverdelete(){
		$filename = $_SESSION['path'].'/'.$_GET['filename'];
		if($_GET['filetype']=='file'){
			if(unlink($filename)){
				$this->DwzCallback('文件删除成功！');
			} else{
				$this->DwzCallback('文件删除失败！',null,300);
			}
		}elseif ($_GET['filetype']=='dir'){
			if(rmdir($filename)){ 
				$this->DwzCallback('目录删除成功！');
		}else {
				$this->DwzCallback('目录不为空或删除失败！');
		}
		}
	}
	//文件移动
	public function move(){
		
			$this->assign('filename',str_replace('//', '/', $_SESSION['path'].'/'.$_GET['filename']));
			$this->display();
		
	}

	//保存移动文件
	public function saveMoveFiles (){
			
			$oldpath = $_POST['filename'];
			$newpath = str_replace('//', '/', $_POST['newpath']);
			if(rename($oldpath, $newpath)) {
				$this->DwzCallback('文件移动成功！');
			}else {
				$this->DwzCallback('文件移动失败！');
			}
	
    }

    /**
     * DWZ Ajax回调
     * 
     */
    public function DwzCallback($message, $navTabId, $statusCode = "200", $rel, $callbackType, $forwardUrl ) {
        $navTabId = $_REQUEST['navTabId'] ;
        $ret = array(
            'statusCode'    => $statusCode,
            'message'       => $message,
            'navTabId'      => $navTabId,
            'rel'           => $rel,
            'callbackType'  => $callbackType,
            'forward'       => $forward,
            );
        $this->ajaxReturn($ret);
    }
}