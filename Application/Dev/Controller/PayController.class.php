<?php
use \Think\Controller;
class PayController extends Controller {
	public function changePayStatus() {
		$ret_code = I('post.ret_code','','trim');
		$sporder_id = I('post.sporder_id','','trim');
		$ordersuccesstime = I('post.ordersuccesstime','','trim');
		$err_msg = I('post.err_msg','','trim');
		
		if( !is_empty($ret_code) && !is_empty($sporder_id) && !is_empty($ordersuccesstime) && !is_empty($err_msg) ){
			$map['orderNumber'] = $sporder_id;
			$re = D('PosterBillLog')->where($map)->limit(1)->select();
			if(!empty($re[0])){
				$data['callbackTime'] = time();
				$data['callbackStatus'] = $ret_code;
				$data['callbackMsg'] = $err_msg;
				$data['callbackSuccessTime'] = $ordersuccesstime;
				D('PosterBillLog')->where($map)->save($data);
			}
		}
	}
}