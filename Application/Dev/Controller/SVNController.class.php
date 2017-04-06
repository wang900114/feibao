<?php
use \Think\Controller;
class SVNController extends Controller {
	public function index() {
		echo "<a href=\"Dev/SVN/update\" target=\"dialog\"><button>更新svn</button></a>
		<a href=\"Dev/SVN/clean\" target=\"dialog\"><button>清理svn</button></a>";
	}

	public function update() {
		ob_start();
		system('C:/wamp/bats/feibao-update-svn.bat',$re);
		$result=ob_get_contents();
		ob_end_clean();
		if(empty($re)){
			echo '<hr/><a style="color:blue;font-size:20px;">更新SVN成功</a><hr/>';
		}else{
			echo '<hr/><a style="color:red;font-size:20px;">更新SVN失败</a><hr/>';
		}
		var_dump(preg_replace('/\-\-username.*luxikun015/isU','',$result));
	}

	public function clean() {
		ob_start();
		system('C:/wamp/bats/feibao-clean-svn.bat',$re);
		$result=ob_get_contents();
		ob_end_clean();
		if(empty($re)){
			echo '<hr/><a style="color:blue;font-size:20px;">更新SVN成功</a>';
			$reUnlink=unlink('C:/wamp/feibao/dir_conflicts.prej');
			($reUnlink) ? print('<hr/><a style="color:blue;font-size:20px;">删除SVN文件锁成功</a><hr/>') : print('<hr/><a style="color:red;font-size:20px;">删除SVN文件锁失败</a><hr/>');
		}else{
			echo '<hr/><a style="color:red;font-size:20px;">更新SVN失败</a><hr/>';
		}
		var_dump(preg_replace('/\-\-username.*luxikun015/isU','',$result));
	}

}