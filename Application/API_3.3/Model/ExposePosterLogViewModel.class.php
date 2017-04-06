<?php
use Think\Model;

// 揭海报<---->海报 的 视图模型
class ExposePosterLogViewModel extends CommonViewModel {

	// protected $tableName = 'expose_poster_log';

	/* 自动验证规则 */
	public $_validate	=	array(
		//array('imei','require','IMEI Required', self::EXISTS_VALIDATE ),
		//array('cityId','require','City ID Required', self::EXISTS_VALIDATE ),
		//array('provinceId','require','Province ID Required', self::EXISTS_VALIDATE ),
		// array('name','require','name必须！'),
	);

	/* 自动完成规则 */
	public $_auto = array(
		// array('image', 'Public/Images/member.gif', self::MODEL_INSERT),
		// array('addTime', 'time', self::MODEL_INSERT,'function'),
	);
	
   public $viewFields = array(
			'Poster'=>array(
						'id'=>'pid',
						'title','del','image','integral','num','total','type','lng','lat'
					),
			'ExposePosterLog'=>array(
						'id','addTime'=>'etime',
						'_on'=>'Poster.id=ExposePosterLog.dataId'
						),
			// 'User'=>array(
						// 'name'=>'username',
						// '_on'=>'Blog.user_id=User.id'
						// ),
		);
	
	/*
	 * 获取 我的海报 列表
	 */
	function getMyPosters(){
		$userId = I('post.userId');
		$type = I('post.type');
		$id = I('post.id');
		$pageSize = I('post.pageSize');
		
		$map['userId'] = $userId;
		$map['del'] = '1';//海报状态 正常
		$map['status'] = '1';//揭海报 状态正常
		
		if(empty($type)){//刷新
			
		}else{//加载
			$map['id'] = array('lt',$id);
		}
		
		$order = 'id desc';
		
		$re = $this->selData($map,$pageSize,'',$order);
		// echo $this->getLastSql();die;
		return $re;
	}
}