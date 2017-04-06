<?php
namespace Dev\Behaviors;
class testBehavior extends \Think\Behavior{  
	public function run(&$param){
		//$this->addDB();
    }
	
	public function addDB(){
		$map['timing'] = '1';
		
		$re = D('MessageUser')->selData($map,'','id,userId,dataId,timingTime');
		
		if(!empty($re)){
			foreach($re as $v){
				$time = time();
				if( $v['timingTime'] < $time ){
					$rePush = $this->pushNotice($v['id'],$v['dataId'], $v['userId']);
					
					$mapU = array();
					$dataU = array();
					$mapU['id'] = $v['id'];
					
					if(empty($rePush["errcode"])){
						$dataU['timing'] = '2';
						$dataU['timingTime'] = $time;
					}else{
						$dataU['timingTime'] = $time;
					}
					$dataU['errcode'] = $rePush["errcode"];
					$dataU['sendno'] = $rePush["sendno"];
					$dataU['errmsg'] = $rePush["errmsg"];
					
					D('MessageUser')->upData($mapU,$dataU);
				}
			}
		}
	}
	
	public function pushNotice($id,$dataId,$userId){
		$mapM['id'] = $dataId;
		$reM = D('Message')->selData($mapM,1);
		if(empty($reM)) return array('errmsg' => '没有此消息记录','errcode'=>-1);
		
		$mapU['id'] = $userId;
		$reU = D('Members')->selData($mapU,1);
		if(empty($reU)) return array('errmsg' => '没有此用户','errcode'=>-2);
		if(empty($reU[0]['jpush'])) return array('errmsg' => '无法找到此用户的设备','errcode'=>-3);
		
		$config = C('PUSH');
		$masterSecret = $config['masterSecret'];
		$appkeys = $config['appkeys'];
		$base_url = $config['base_url'];
		
		$title = empty($reM[0]['title']) ? $config['title'] : $reM[0]['title'];
		$content = $reM[0]['content'];
		$sendno = $id;
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
		
		return $res_arr;
	}
}