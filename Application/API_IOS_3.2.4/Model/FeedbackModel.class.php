<?php
use Think\Model;

// 意见反馈 模型
class FeedbackModel extends CommonModel {
/*
	protected $insertFields = array('id','userId','content');
	protected $updateFields = array('content');

	//自动验证规则
	public $_validate	=	array(
		array('userId','require','用户ID必须'),
		array('content','require','内容必须'),
                array('phone','require','内容必须'),
		array('content','','已经反馈过了，请不要重复提交！',self::EXISTS_VALIDATE,'unique',self::MODEL_INSERT),
		);

	//自动完成规则
	public $_auto = array(
		array('addTime', 'time', self::MODEL_INSERT,'function'),
	);
*/
	/**
	 * 检测内容长度是否小于10个字
	 * @access public
	 * @param string $content
	 * @return boolean
	 * @author FrankKung <kongfanjian@andlisoft.com>
	 */
	public function checkContent($content='') {
            return ( mb_strlen($content,'UTF8') < 10 ) ? false : true ;
	}
        
        //添加反馈信息
        public function addContent($userId,$content){
            $data = M("Feedback")->where('userId='.$userId.' and content="'.$content.'"')->field("id")->find();
            //var_dump(M("Feedback")->getLastSql());die;
            if($data){
                return 2;
            }else{
                // 构建数据
                $data = array(
                    'userId' => $userId,
                    'content' => $content,
                    'addTime' => time(),
                );
                
                // 保存数据
                $id = M("Feedback")->data($data)->add();
                //echo M("Feedback")->getLastSql();die;
                return $id;
            }
        }

}