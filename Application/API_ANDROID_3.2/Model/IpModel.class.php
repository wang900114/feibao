<?php

/**
 * ip处理类
 * 
 */
use Think\Model;

class IpModel extends CommonModel {

    //导入禁止的ip
    public function filter($ip) {
        //$ip='127.0.0.2';
        //$ip = empty($ip) ? $_SERVER['SERVER_ADDR'] : $ip;
        $ip = empty($ip) ? $this->getIp() : $ip;
        

        $result = D('Ip')->where('ip ="' . $ip . '"')->field('ip,status')->select();

        if (empty($result)) {//判断数据库中是否存在该ip
            $data = array(
                'ip' => $ip,
                'time' => time(),
            );
            // 保存数据
            $id = D('Ip')->add($data);
            //return ture;
        } else {
            if ($result[0]['ip'] == $ip && $result[0]['status'] == '0') {//判断ip状态是否正确
                $where['ip'] = $result[0]['ip'];
                $data = array(
                    'status' => '1',
                );
                //var_dump($data);die;

                $result = D('Ip')->where($where)->save($data);
            }
        }

        $list = D('Ip,status')->field('ip')->select();
        //var_dump($list);die;

        S('list', $list, 360000000);
        //var_dump($list);

        return true;
    }

	function getIp(){
	     static $realip;
	     if (isset($_SERVER)){
	         if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
	             $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
	         } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
	             $realip = $_SERVER["HTTP_CLIENT_IP"];
	         } else {
	             $realip = $_SERVER["REMOTE_ADDR"];
	         }
	     } else {
	         if (getenv("HTTP_X_FORWARDED_FOR")){
	             $realip = getenv("HTTP_X_FORWARDED_FOR");
	         } else if (getenv("HTTP_CLIENT_IP")) {
	             $realip = getenv("HTTP_CLIENT_IP");
	         } else {
	             $realip = getenv("REMOTE_ADDR");
	         }
	     }
	     return $realip;
	}

    //查询禁止的ip
    public function filtersel($ip) {
        //$ip='127.0.0.8';

        $value = S('list');

        if (empty($value)) {
            $list = D('Ip')->field('ip,status')->select();


            S('list', $list, 360000000);
            $value = S('list');
        }

        //var_dump($value);die;


        $flag = 0;
        foreach ($value as $k => $v) {
            //过滤掉本机和集群的数据
            if ($v['ip'] == $ip && $v['status'] == '1' && $v['ip'] != "127.0.0.1" && $v['ip'] != "172.31.255.155" && $v['ip'] != "172.31.255.156") {
                $flag = 1;
            }
        }
        //var_dump($flag);die;

        if ($flag) {
            return 1; //需要过滤
        } else {
            return 0;
        }
        //var_dump($m->get('ip'));die;
    }

}
