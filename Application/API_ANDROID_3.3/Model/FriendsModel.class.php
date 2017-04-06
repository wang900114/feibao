<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FriendsModel
 *
 * @author wangwei
 */
use Think\Model;

// 发现模型
class FriendsModel extends CommonModel {
    
    /**
     * 我的朋友列表
     * @param int $userId
     * @param int $selectTime
     * @param int $page
     * @param int $pageSize
     * @return type
     */
    function getListData($userId) {
        $field = 'm.image,m.name AS nickName,m.integral,m.signture,m.id AS userId';
        $join = " RIGHT JOIN lu_members AS m ON f.fuid = m.id";
//        $limit = ' limit ' . ($page - 1) * $pageSize . "," . $pageSize;
        $where = "WHERE `uid` = $userId AND f.status = '1'";
        $order = ' order by m.integral desc';
        
//        select m.image,m.name AS nickName,m.integral,f.fuid AS userId,m.groupType from lu_friend as f AS f RIGHT JOIN lu_members AS m ON f.fuid = m.id WHERE f.addTime <= 1453877132 AND `uid` = 95344 AND f.status = '1' m.integral desc 0,5
        
        
        $sql = "select $field from lu_friend as f $join $where $order";
        $result = M()->query($sql);
//        echo M()->getLastSql();
        return $result;
    }
    
    /**
     * 搜索我的朋友列表
     * @param int $userId
     * @param int $selectTime
     * @param int $page
     * @param int $pageSize
     * @return type
     */
    function getSearchListData($userId, $content, $page = 0, $pageSize = 10) {        
        $field = ' m.id AS userId, m.signture,m.image,m.name AS nickName ';
        $where = " where m.freeze = '0' and (m.name like '%$content%' or phone like '%$content%') and m.name not like '飞报官方推荐' ";
        $limit = " limit $page,$pageSize ";
        $sql = "select $field from lu_members as m $where $limit";
//        echo $sql;exit;
//        return $sql;
        $result = M()->query($sql);
        if($result)
        {
            foreach($result as $k => $v)
            {
                $f_sql = "select status from lu_friend where uid = $userId and fuid = {$v['userId']}";
                $f_rs = M()->query($f_sql);
                $result[$k]['isFriend'] = $f_rs[0]['status'] == 1 ? 1 : 2;
//                $result[$k]['test'] = $sql;
            }
        }
//        var_dump($result);exit;
        return $result;
    }
    
    /**
     * 搜索通讯录中朋友列表接口
     * @param int $userId
     * @param int $selectTime
     * @param int $page
     * @param int $pageSize
     * @return type
     */
    function getSearchListByPhone($userId, $content) {
        $field = ' m.id AS userId, m.signture,m.image,m.name AS nickName ';
        $where = " where m.freeze = '0' and m.phone in ($content) ";
        $sql = "select $field from lu_members as m $where ";
//        return $sql;
        $result = M()->query($sql);
        if($result)
        {
            foreach($result as $k => $v)
            {
                $f_sql = "select status from lu_friend where uid = $userId and fuid = {$v['userId']}";
                $f_rs = M()->query($f_sql);
                $result[$k]['isFriend'] = $f_rs[0]['status'] ? $f_rs[0]['status'] : 2;
//                $result[$k]['test'] = $sql;
            }
        }
        return $result;
//        $field = 'm.signture,m.image,m.name AS nickName,f.fuid AS userId,if(f.status=1,1,2) as isfriend';
//        $join = " RIGHT JOIN lu_members AS m ON f.fuid = m.id";
//        $where = " where m.freeze = '0' and m.phone in ($content) ";
//        $sql = "select $field from lu_friend as f $join $where ";
//        $result = M()->query($sql);
//        echo $sql;exit;
//        return $result;
    }
    
    /**
     * 获得店铺信息
     * @param sting $friendId
     * @return boolean
     */
    function getShopInfo($friendId) {
        $friendId = decodePass($friendId);
        if (empty($friendId)) {
            return false;
        }
        $where = array('userId' => $friendId);
        $fields = 'shopName AS title ,shopAddress AS address,shopContent AS content,shopPhone AS telphone,webUrl AS netAddress,image1,image2,image3,image4,image5, addTime';
//        $fields = 'shopName AS title ,shopAddress AS address,shopContent AS content,shopPhone AS telphone,webUrl AS netAddress,image1,image2,image3,image4,image5';
        $result = M("members_shop")->where($where)->field($fields)->find();
        
        //是否关注
        $map = array(
            'userId' => intval($this->userId),
            'focusId' => intval($friendId)
        );
        $isFocus = D('focus_merchants')->where($map)->find();
        $result['isFocus'] = $isFocus ? 1 : 2;
        
        
        
//        echo M("members_shop")->getLastSql();
//        if ($result) {
//            $result['title'] = str_replace('"', "\"", $result['title']);
//            $result['shopContent'] = str_replace('"', "\"", $result['shopContent']);
//            $result['address'] = str_replace('"', "\"", $result['address']);
//        }
        return $result;
    }
    
    /**
     * 屏蔽朋友
     * @param int $userId
     * @param string $friendId
     */
    function shield($userId, $friendId, $type = '1') {
        $flag = 1;
        $fuid = decodePass($friendId);
        //$fuid = $friendId;
        if (empty($fuid) || $userId == $friendId) {
            return false;
        }
        $fresult = $this->getUserInfoById($fuid, 'id,freeze');
        if ($fresult) {
            if ($fresult['freeze'] != 0) {
                $flag = 2; //屏蔽的朋友不是正常状态
            } else {
                $result = $this->getUserInfoById($userId, 'id,freeze');
                if ($result['freeze'] != 0) {
                    $flag = 3; //当前用户状态不正常
                } else {
                    $fwhere['uid'] = $userId;
                    $fwhere['fuid'] = $fuid;
                    $findResult = M("friend_shield")->where($fwhere)->find();
                    //echo M("friend_shield")->getLastSql();
                    if ($type == '1') {//屏蔽操作
                        if ($findResult['status'] == '1') {
                            $flag = 4; //已屏蔽过
                        } else if ($findResult['status'] == '2') {
                            $where['uid'] = $userId;
                            $where['fuid'] = $fuid;
                            $data['addTime'] = time();
                            $data['status'] = '1';
                            $addResult = M("friend_shield")->data($data)->where($where)->save();
                            //echo $this->getLastSql();
                            $action = '添加关注';
                            if ($addResult) {
                                $flag = 5; //屏蔽成功
                                $this->friendShieldLog($userId, $fuid, $action); //写入日志
                            } else {
                                $flag = 6;
                            }
                        } else {
                            //屏蔽频次计算 计算最近10 小于10秒
                            $aflag = $this->attentionFrequency($userId);
                            if ($aflag == 2) {
                                $flag = 8;
                            } else {
                                $data['uid'] = $userId;
                                $data['fuid'] = $fuid;
                                $data['addTime'] = time();
                                $data['status'] = '1';
                                $addResult = M("friend_shield")->data($data)->add();
                                //echo $this->getLastSql();
                                $action = '添加屏蔽';
                                if ($addResult) {
                                    $flag = 5; //关注成功
                                    $this->friendShieldLog($userId, $flag, $action);
                                } else {
                                    $flag = 6;
                                }
                            }
                        }
                    } else {//取消屏蔽
                        if ($findResult['status'] == '2') {
                            $flag = 5;
                        } else {
                            if ($findResult) {
                                $data['cancelTime'] = time();
                                $data['status'] = '2';
                                $addResult = M("friend_shield")->data($data)->where(array('uid' => $userId, 'fuid' => $fuid))->save();
                                $action = '取消屏蔽';
                                if ($addResult) {
                                    $flag = 5;
                                    $this->friendShieldLog($userId, $flag, $action);
                                } else {
                                    $flag = 6;
                                }
                            } else {
                                $flag = 9; //屏蔽记录不存在
                            }
                        }
                    }
                }
            }
        } else {
            $flag = 7; //屏蔽朋友不存在
        }
        return $flag;
    }
    
    
    
     /**
     * 3.3官方消息用户列表
     * @param int $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function officialForwardUser()
    {
        $field = " id as userId, name as nickName, image, signture, addTime";
        $where = " where `name` = '飞报官方推荐' ";
//        $where = "";
        $order = " order by addTime desc ";
        $limit = " limit 1 ";
        $sql = "select $field from lu_members $where $order $limit ";
//        echo $sql;
        $return = M()->query($sql);
        return $return;
        
        $field = " b.id as userId, b.name as nickName, b.image, b.signture, a.add_time as addTime";
        $where = " where a.is_recommend = '1' and a.status ='2' ";
//        $where = "";
        $order = " order by a.add_time desc ";
        $limit = " limit 1 ";
        $sql = "select $field from lu_advertising_base as a left join lu_members as b on a.user_id = b.id $where $order $limit ";
        $return = M()->query($sql);
        return $return;
    }
    
     /**
     * 3.3官方消息用户列表
     * @param int $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
//    public function officialForwardUser()
//    {
//        $field = " b.id as userId, b.name as nickName, b.image, b.signture, a.addTime, a.isNew ";
////        $where = " where a.userId = $userId and a.status ='1' ";
//        $where = "";
//        $order = " order by a.addTime desc ";
//        $limit = " limit 1 ";
//        $sql = "select $field from lu_official_forward as a left join lu_members as b on a.userId = b.id $where $order $limit ";
//        $return = M()->query($sql);
//        return $return;
//    }
    
     /**
     * 3.3消息用户列表
     * @param int $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    function forwardUserList($userId, $lastAddTime = 0, $pageSize = 20) {
        $field = " b.id as userId, b.name as nickName, b.image, b.signture, a.updateTime as addTime, a.isNew ";
        $where = " where a.userId = $userId";
        if($lastAddTime)
        {
            $where .= " and a.updateTime < $lastAddTime ";
        }
        $order = " order by a.updateTime desc ";
        $limit = " limit $pageSize ";
        $sql = "select $field from lu_new_message as a left join lu_members as b on a.friendId = b.id $where $order $limit ";
//        echo $sql;exit;
        $return = M()->query($sql);
        return $return;
    }
    
     /**
     * 3.3朋友消息详情
     * @param int $userId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    function messageDetail($userId, $friendId, $lastAddTime = 0, $pageSize = 10) {
        $field = " b.name, b.signture, a.addTime, a.isNew, a.dataId, a.type, b.id as friendId, b.image as friendImage ";
        $where = " where ((a.userId = $userId and a.friendId = $friendId) or (a.userId = $friendId and a.friendId = $userId))";
        if($lastAddTime > 0)
        {
            $where .= "  and a.addTime < $lastAddTime ";
        }
        $order = " order by a.addTime desc ";
        $limit = " limit $pageSize ";
        $sql = "select $field from lu_friend_forward_new as a left join lu_members as b on a.userId = b.id $where $order $limit ";
//        echo $sql;exit;
        $return = M()->query($sql);
        
        if($return)
        {
            foreach($return as $k => $v)
            {
                switch($v['type'])
                {
                    case 1:
                        $tableName = 'lu_advertising_base';
                        $field = "id as adv_id, title, image";
                        break;
                    case 2:
                        $tableName = 'lu_service';
                        $field = "adv_id, name as title, price, image";
                        break;
                    case 3:
                        $tableName = 'lu_advertising_preferential';
                        $field = "adv_id, title, number as price, image, type as isPreferential";
                        break;
                    case 4:
                        $tableName = 'lu_commodity';
                        $field = "adv_id, name as title, price, image";
                        break;
                    case 5:
                        $tableName = 'lu_activity';
                        $field = "adv_id, name as title, price, image";
                        break;
                }
                $sql = "SELECT $field FROM `$tableName` WHERE ( id={$v['dataId']} ) LIMIT 1";
                $info = M()->query($sql);
                $return[$k]['title'] = $info[0]['title'];
                $return[$k]['image'] = $info[0]['image'];
                $return[$k]['advId'] = $info[0]['adv_id'];
                if($info[0]['price'])
                {
                    if($info[0]['isPreferential'] == 1)
                    {
                        $return[$k]['price'] = $info[0]['price'];
                    }else
                    {
                        $return[$k]['price'] = $this->fenZhuanYuan($info[0]['price']);
                    }
                    
                }
                if($info[0]['isPreferential'])
                {
                    $return[$k]['isPreferential'] = $info[0]['isPreferential'];
                }
            }
        }
        return $return;
    }
    
    
    function fenZhuanYuan($fen){
        $fen = $fen / 100;
        $pos = strpos($fen, '.');
        if($pos){
            $str = substr($fen, $pos + 1);
            if(strlen($str) == 1){
                return $fen . '0';
            }else{
                return $fen;
            }
        }else{
            return $fen . '.00';
        }
    }
    
}