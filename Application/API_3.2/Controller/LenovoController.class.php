<?php
/**
 * 联想 接口
 * @author Jine <luxikun@andlisoft.com>
 */
class LenovoController extends CommonController {
	/**
	 * 初始化
	 */
	public function _initialize() {
		//header('Content-Type:application/json; charset=UTF-8');
		
		parent::_initialize();
		
		//A('API_3.2/Public')->testPersonalToken();//验证 个人 token
	}
	
	/**
     * 创建SESSION和KEY信息的数据
     * @return json
     */
	public function setData() {
		
		
		$return['success'] = true;
		
		// if( is_empty($userId) || is_empty($phone) || is_empty($money) ){
			// $return['status'] = -888;
			// $return['message'] = '传参不完整';
		// }else{
			$upload = A('API_3.2/Upload');
			$re = $upload->setSession();
			
			$return['re']=$re;
			header('Content-Type:application/json; charset=UTF-8');
			
			if( is_array($re) && $re['status'] < 0 ){
				$return['status'] = -1;
				$return['message'] = '创建失败';
				$return['info'] = (object)array();
			}else{
				$return['status'] = 1;
				$return['message'] = '创建成功';
				$return['info'] = $re;
			}
		
		// }
		echo jsonStr($return);exit(0);
	}
}