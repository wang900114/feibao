<?php

use Think\Model;

/**
 * 系统开关处理类
 * @author Charles <homercharles@qq.com>
 */
class SystemModel extends CommonModel {

    /**
     * 系统功能状态
     * 判断并处理系统状态,如果系统异常则直接终止服务
     * @return int 开关状态,当且仅当为1的时候为正常状态
     */
    public function systemSwitchStatus() {
        $where['key'] = 'system_switch_3_1';
        $result = $this->readDataAndSynchronousDataLinkControl($where);
        $data = (int)$result['value'];

        $data = $data == 1 ? 1 : $data;
        return $data;
    }

    /**
     * 兑换接口开关状态
     * 判断并处理后台是否开启兑换接口,兑换接口如果关闭则应该立刻终止服务
     * @return int 开关状态,当且仅当为1的时候为正常状态
     */
    public function exchangeSwitchStatus() {
        $where['key'] = 'exchange_switch_3_1';
        $result = $this->readDataAndSynchronousDataLinkControl($where);
        $data = (int)$result['value'];
        $data = $data == 1 ? 1 : $data;
        return $data;
    }
    
    //ios会员邀请状态
    public function iosInviteStatus(){
        $where['key'] = 'ios_invite_status';
        $result = $this->readDataAndSynchronousDataLinkControl($where);
        $data = (int)$result['value'];
        $data = $data == 1 ? 1 : $data;
        return $data;
    }
    
    /**
     * 邀请接口开关状态
     * 判断并处理后台是否开启兑换接口,兑换接口如果关闭则应该立刻终止服务
     * @return int 开关状态,当且仅当为1的时候为正常状态
     */
    public function inviteSwitchStatus() {
        $where['key'] = 'invite_switch_3_1';
        $result = $this->readDataAndSynchronousDataLinkControl($where);
        $data = (int)$result['value'];
        $data = $data == 1 ? 1 : $data;
        return $data;
    }
    
    /**
     * 读取IP访问时间限制
     * 
     * @return int 返回IP访问的时间限制,单位分钟
     */
    public function ipAccessMinute(){
        $where['key'] = 'ip_access_minute';
        $result = $this->readDataAndSynchronousDataLinkControl($where);
        return (int)$result['value'];
    }
    
     /**
     * 读取IP访问次数限制
     * 
     * @return int 返回IP访问的时间限制,单位分钟
     */
    public function ipAccessMax(){
        $where['key'] = 'ip_access_max';
        $result = $this->readDataAndSynchronousDataLinkControl($where);
        return (int)$result['value'];
    }
    
    /**
     * 读取指定的配置
     * 
     * @param type $key 需要读取的字段
     * @return type 返回需要读取的字段的值
     */
    public function readConfig($key){
        $where['key'] = $key;
        $result = $this->readDataAndSynchronousDataLinkControl($where);
        return $result['value'];
    }

    /**
     * 读取需要数据和同步数据链路控制
     * 读取需要的数据,如果未发现则同步数据并读取
     * 
     * @param array $where 需要的数据的读取条件
     * @return array 返回需要的数据
     */
    public function readDataAndSynchronousDataLinkControl($where) {
        $config_member =  D('config_member');
        $config = D("config");
        //从缓存读取需要的数据

        $data = $config_member->where($where)->find();
        //var_dump($data);die;

        //如果未取到数据,则更新相关数据到缓存表并读取相关数据
        if(!$data){
            //删除缓存表所有数据
            $config_member->where("1")->delete();
            $all_data = $config->select();
            foreach($all_data as $k => $v){
                $config_member->add($v);
            }
            //读取需要的数据
            $data = $config_member->where($where)->find();
        }
        return $data;
    }

}
