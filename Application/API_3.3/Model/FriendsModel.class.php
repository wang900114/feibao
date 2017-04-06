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
class FriendModel extends CommonModel {
    
    /**
     * 我的朋友列表
     * @param int $userId
     * @param int $selectTime
     * @param int $page
     * @param int $pageSize
     * @return type
     */
    function getListData($userId, $selectTime, $page, $pageSize) {
        $field = 'm.image,m.name AS nickName,m.integral,f.fuid AS userId,m.groupType';
        $join = " AS f RIGHT JOIN __MEMBERS__ AS m ON f.fuid = m.id";
        $limit = ($page - 1) * $pageSize . "," . $pageSize;
        $where = array(
            'f.addTime' => array('elt', $selectTime),
            'uid' => $userId,
            'f.status' => '1'
        );
        $order = ' m.integral desc';
        $result = $this->field($field)->where($where)->order($order)->join($join)->limit($limit)->select();
        //echo $this->getLastSql();
        return $result;
    }
}