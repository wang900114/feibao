<?php
use Think\Controller;
class JPushController extends Controller{
	public function pushNoticeWithOutDB($userId,$content,$title=''){
		$config = C('PUSH');
		$masterSecret = $config['masterSecret'];
		$appkeys = $config['appkeys'];
		$base_url = $config['base_url'];
		
		$mapU['id'] = $userId;
		$reU = D('Members')->selData($mapU,1);
		if(empty($reU)) return array('errmsg' => '没有此用户','errcode'=>-2);
		if(empty($reU[0]['jpush'])) return array('errmsg' => '无法找到此用户的设备','errcode'=>-3);
		
		$titles = empty($title) ? $config['title'] : $title;
		$sendno = 1;
		$receiver_type = 5;
		$receiver_value = $reU[0]['jpush'];
		$platform = $config['platform'] ;
		
		$arr = array(
					'n_builder_id'=>0, 
					'n_title'=>$titles, 
					'n_content'=>$content,
				);
		$msg_content = json_encode($arr);
		
		$obj = new \Org\Util\JPush($masterSecret,$appkeys,$base_url);
		$res_arr = $obj->send($sendno, $receiver_type, $receiver_value, 1, $msg_content, $platform);
		
		return $res_arr;
	}

	public function pushNotice($dataId,$userId){
		$mapM['id'] = $dataId;
		$reM = D('Message')->selData($mapM,1);
		if(empty($reM)) return array('errmsg' => '没有此消息记录','errcode'=>-1);
		
		$mapU['id'] = $userId;
		$reU = D('Members')->selData($mapU,1);
		if(empty($reU)) return array('errmsg' => '没有此用户','errcode'=>-2);
		if(empty($reU[0]['jpush'])) return array('errmsg' => '无法找到此用户的设备','errcode'=>-3);
		
		$data['userId'] = $userId;
		$data['dataId'] = $dataId;
		$data['addTime'] = 0;
		$data['errcode'] = 0;
		$data['sendno'] = 0;
		$data['errmsg'] = 0;
		$data['total_user'] = 1;
		$data['send_cnt'] = 1;
		$reId = D('MessageUser')->addData($data);
		
		$config = C('PUSH');
		$masterSecret = $config['masterSecret'];
		$appkeys = $config['appkeys'];
		$base_url = $config['base_url'];
		
		$title = empty($reM[0]['title']) ? $config['title'] : $reM[0]['title'];
		$content = $reM[0]['content'];
		$sendno = $reId;
		if(empty($reU[0]['jpush'])){
			$receiver_type = 4;
			$receiver_value = '';
		}else{
			$receiver_type = 5;
			$receiver_value = $reU[0]['jpush'];
		}
		$platform = $config['platform'] ;
		
		$arr = array(
					'n_builder_id'=>0, 
					'n_title'=>$title, 
					'n_content'=>$content
				);
		$msg_content = json_encode($arr);
		
		$obj = new \Org\Util\JPush($masterSecret,$appkeys,$base_url);
		$res_arr = $obj->send($sendno, $receiver_type, $receiver_value, 1, $msg_content, $platform);
		
		$map['id'] = $reId;
		// if($res_arr['errcode'] === 0){
			$data2['userId'] = $userId;
			$data2['dataId'] = $dataId;
			$data2['addTime'] = time();
			$data2['errcode'] = $res_arr['errcode'];
			$data2['sendno'] = $sendno;
			$data2['errmsg'] = $res_arr['errmsg'];
			$data2['total_user'] = 1;
			$data2['send_cnt'] = 1;
			D('MessageUser')->upData($map,$data2);
		// }else{
			// D('MessageUser')->delData($map);
		// }
		
		return $res_arr;
	}
	
	public function pushNoticeAll($dataId){
		$mapM['id'] = $dataId;
		$reM = D('Message')->selData($mapM,1);
		if(empty($reM)) return array('errmsg' => '没有此消息记录','errcode'=>-1);
		
		$config = C('PUSH');
		$masterSecret = $config['masterSecret'];
		$appkeys = $config['appkeys'];
		$base_url = $config['base_url'];
		
		$title = empty($reM[0]['title']) ? $config['title'] : $reM[0]['title'];
		$content = $reM[0]['content'];
		$sendno = D('MessageUser')->max('id');
		if(empty($sendno)) $sendno = 1;
		$receiver_type = 4;
		$receiver_value = '';
		$platform = $config['platform'] ;
		
		$arr = array(
					'n_builder_id'=>0, 
					'n_title'=>$title, 
					'n_content'=>$content
				);
		$msg_content = json_encode($arr);
		
		$obj = new \Org\Util\JPush($masterSecret,$appkeys,$base_url);
		$res_arr = $obj->send($sendno, $receiver_type, $receiver_value, 1, $msg_content, $platform);
		
		if($res_arr['errcode'] === 0){
			$allUser = D('Members')->selData(array('freeze'=>'0','jpush'=>array('neq',''),'type'=>'3.0'),'','id,freeze');
			$total_user = count($allUser);
			$time = time();
			if(!empty($allUser)){
				foreach($allUser as $v){
					$data['userId'] = $v['id'];
					$data['dataId'] = $dataId;
					$data['addTime'] = $time;
					$data['errcode'] = $res_arr['errcode'];
					$data['sendno'] = $sendno;
					$data['errmsg'] = $res_arr['errmsg'];
					$data['total_user'] = $total_user;
					$data['send_cnt'] = $total_user;
					D('MessageUser')->addData($data);
				}
			}
		}
		
		return $res_arr;
	}
	
	public function pushNoticeAllWithOutDB($content,$title=''){
		$config = C('PUSH');
		$masterSecret = $config['masterSecret'];
		$appkeys = $config['appkeys'];
		$base_url = $config['base_url'];
		
		$titles = empty($title) ? $config['title'] : $title;
		$sendno = 1;
		$receiver_type = 4;
		$receiver_value = '';
		$platform = $config['platform'] ;
		
		$arr = array(
					'n_builder_id'=>0, 
					'n_title'=>$titles, 
					'n_content'=>$content
				);
		$msg_content = json_encode($arr);
		
		$obj = new \Org\Util\JPush($masterSecret,$appkeys,$base_url);
		$res_arr = $obj->send($sendno, $receiver_type, $receiver_value, 1, $msg_content, $platform);
		
		return $res_arr;
	}
	
	public function pushMessage($content){
		if(empty($content)) return array('errmsg' => '没有内容');
		
		$config = C('PUSH');
		$masterSecret = $config['masterSecret'];
		$appkeys = $config['appkeys'];
		$base_url = $config['base_url'];
		
		$sendno = 1;
		$receiver_value = '';
		$platform = $config['platform'] ;
		$arr = array(
					'message'=>$content
				);
		$msg_content = json_encode($arr);
		
		$obj = new \Org\Util\JPush($masterSecret,$appkeys,$base_url);
		$res_arr = $obj->send($sendno, 4, $receiver_value, 2, $msg_content, $platform);
		
		return $res_arr;
	}
}