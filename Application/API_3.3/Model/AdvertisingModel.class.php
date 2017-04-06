<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AdvertisingModel
 *
 * @author wangwei
 */

use Think\Model;

class AdvertisingModel extends CommonModel {
    
    /**
     * 
     * 获取广告列表
     */
    public function lists($friendId, $order, $offset = 0, $pageTotal = 5)
    {
        $sql = "select a.id, a.user_id userId, b.image userImage, a.comment_count commentTotal, a.interest_count interestTotal, a.collection_count collectionTotal, a.image advertisingImages, a.tel, a.web_url webUrl, a.address a.content, a.red_number reNumber, a.red_remarks redRemarks from lu_advertising_base a join left lu_members on a.user_id = b.id";
        $rs = M()->query($sql);
        return $rs;
    }
    
    /**
     * 
     * 根据广告id获取广告详情
     */
    public function detail($id)
    {
        $sql = "select a.id, a.user_id userId, b.image userImage, a.comment_count commentTotal, a.interest_count interestTotal, a.collection_count collectionTotal, a.image advertisingImages, a.tel, a.web_url webUrl, a.address a.content, a.red_number reNumber, a.red_remarks redRemarks from lu_advertising_base a join left lu_members on a.user_id = b.id where a.id = {$id}";
        $rs = M()->query($sql);
        return $rs;
    }
    
//    /**
//     * 根据广告id获取广告基础信息
//     */
//    public function getBaseByAdvId($advId)
//    {
//        $sql = 'select * from lu_advertising_base';
//    }
    
    /**
     * 
     * 根据广告id获取标签列表
     */
    public function getTagyByAdvId($advId)
    {
        $sql = "select b.cid, b.name from lu_poster_type_relation as a left join lu_poster_tags as b on a.dataId = b.cid where a.dataId = {$advId} and a.categoryType = '3' order by b.id desc"; 
        $rs = M()->query($sql);
        return $rs;
    }
    
    /**
     * 
     * 根据广告id获取商品列表
     */
    public function getCommodityByAdvId($advId)
    {
        $sql = "select id, name, price, image, add_time as addTime from lu_commodity where adv_id = {$advId} order by add_time desc"; 
        $rs = M()->query($sql);
        return $rs;
    }
    
    /**
     * 
     * 根据广告id获取广告优惠/代金券列表
     */
    public function getPreferentialByAdvId($advId)
    {
        $sql = "select id, name, number as price, image, add_time as addTime from lu_advertising_preferential where adv_id = {$advId} order by add_time desc"; 
        $rs = M()->query($sql);
        return $rs;
    }
    
    /**
     * 
     * 根据广告id获取广告服务列表
     */
    public function getServiceByAdvId($advId)
    {
        $sql = "select id, name, price, image, add_time as addTime from lu_service where adv_id = {$advId} order by add_time desc"; 
        $rs = M()->query($sql);
        return $rs;
    }
    
    /**
     * 
     * 根据用户id和广告id判断用户是否收藏（是否感兴趣） 1是 2否 3没有数据
     */
    public function isInterest($userId, $advId, $type = 1)
    {
        $sql = "select whether from lu_interact_log where adv_id = {$advId} and user_id = {$userId} and type = '{$type}' "; 
        $rs = M()->query($sql);
        return $rs ? $rs['whether'] : 3;
    }
    
    /**
     * 
     * 添加用户收藏（感兴趣）
     */
    public function addInterest($userId, $advId, $type = '2', $whether = '1')
    {
        $time = time();
        $sql = "insert into lu_interact_log (user_id, adv_id, type, whether, add_time) values($userId, $advId, '$type', '$whether', $time)"; 
        $rs = M()->execute($sql);
        return $rs;
    }
    
    /**
     * 
     * 广告添加留言
     */
    public function addLeaveWord($userId, $advId, $content)
    {
        $time = time();
        $sql = "insert into lu_leave_word (user_id, adv_id, content, add_time) values($userId, $advId, '$content', $time)"; 
        $rs = M()->execute($sql);
        return $rs;
    }
    
    /**
     * 
     * 根据广告id获取广告详情
     */
    public function leaveLists($advId, $offset = 0, $pageTotal = 5)
    {
        $sql = "select a.user_id as userId, c.name as userName, c.image as userImage, a.add_time as addTime, a.content, d.name as merchantName, b.content as merchantCont, b.add_time as merchantAddTime from lu_leave_word as a left join lu_leave_word as b on a.id = b.pid left join lu_members as c on a.user_id = c.id left join lu_members as d on b.user_id = d.id where a.pid = 0 and adv_id = $advId order by a.add_time desc limit $offset,$pageTotal";
        $rs = M()->query($sql);
        return $rs;
    }
    
    /**
     * 领取红包添加记录
     */
    public function getRedBag($userId, $advId)
    {
        $time = time();
        $sql = "insert into lu_red_bag_log (user_id, adv_id, add_time) values($userId, $advId, $time)"; 
        $rs = M()->execute($sql);
        return $rs;
    }
    
    /**
     * 用户领取红包
     */
    public function userGetRedBag($userId, $money)
    {
        $money = $money / 100;
        $time = time();
        $sql = "update lu_members set `total_money` = `total_money` + $money, `money` = `money` = `money` + $money where `id` = $userId"; 
        $rs = M()->execute($sql);
        return $rs;
    }
    
    
    
    
    
}
