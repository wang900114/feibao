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
        $sql = "select a.id, a.user_id userId, b.image userImage, a.comment_count commentsTotal, a.interest_count interestTotal, a.collection_count collectionTotal, a.image advertisingImages, a.tel, a.web_url webUrl, a.address a.content, a.red_number redNumber, a.red_remarks redRemarks from lu_advertising_base a join left lu_members on a.user_id = b.id";
        $rs = M()->query($sql);
        return $rs;
    }
    
    /**
     * 
     * 根据广告id获取广告详情
     */
    public function detail($id)
    {
        $sql = "select a.id, a.status, a.title, a.user_id userId, b.name as userName, b.image userImage, a.comment_count commentsTotal, a.interest_count interestTotal, a.collection_count collectionTotal, a.image advertisingImages, a.tel, a.web_url webUrl, a.address, a.content, a.red_number redNumber, a.red_remarks redRemarks, a.add_time as addTime from lu_advertising_base a left join lu_members b on a.user_id = b.id where a.id = {$id} and a.status = '2'";
//        echo $sql;
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
        $sql = "select b.cid, b.name from lu_poster_type_relation_new as a left join lu_poster_tags as b on a.dataId = b.cid where a.dataId = {$advId} and a.categoryType = '3' order by b.id desc"; 
        $rs = M()->query($sql);
        return $rs;
    }
    
    /**
     * 
     * 根据广告id获取商品列表
     */
    public function getCommodityByAdvId($advId)
    {
        $sql = "select id, name, price, image, add_time as addTime, buy_url as buyLink from lu_commodity where status = '1' and adv_id = {$advId} order by add_time desc"; 
        $rs = M()->query($sql);
        return $rs;
    }
    
    /**
     * 
     * 根据广告id获取广告优惠/代金券列表
     */
    public function getPreferentialByAdvId($advId)
    {
        $sql = "select id, title name, number as price, image, add_time as addTime, type from lu_advertising_preferential where status = '1' and adv_id = {$advId} order by add_time desc"; 
        $rs = M()->query($sql);
        return $rs;
    }
    
    /**
     * 
     * 根据广告id获取广告服务列表
     */
    public function getServiceByAdvId($advId)
    {
        $sql = "select id, name, price, image, add_time as addTime from lu_service where status = '1' and adv_id = {$advId} order by add_time desc"; 
        $rs = M()->query($sql);
        return $rs;
    }
    
    /**
     * 
     * 根据广告id获取广告活动列表
     */
    public function getActivityByAdvId($advId)
    {
        $sql = "select id, name, price, image, add_time as addTime, buy_url as buyLink from lu_activity where status = '1' and adv_id = {$advId} order by add_time desc"; 
        $rs = M()->query($sql);
        return $rs;
    }
    
    /**
     * 
     * 根据用户id和广告id判断用户是否收藏 1是 2否 3没有数据
     */
    public function isCollection($userId, $advId)
    {
        $sql = "select status from lu_collect_poster_log_new where dataId = {$advId} and userId = {$userId} "; 
//        echo $sql;exit;
        $rs = M()->query($sql);
        return $rs[0]['status'] ? $rs[0]['status'] : 2;
    }
    
    /**
     * 
     * 根据用户id和广告id判断用户是否收藏（是否感兴趣） 1是 2否 3没有数据
     */
    public function isInterest($userId, $advId, $type = 1)
    {
        $sql = "select whether from lu_interact_log where adv_id = {$advId} and user_id = {$userId} and type = '{$type}' "; 
        $rs = M()->query($sql);
        return $rs ? $rs[0]['whether'] : 3;
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
        if($type == 2 && $whether == 1)
        {
            $sql = "update lu_advertising_base set interest_count = interest_count + 1  where id = $advId";
            M()->execute($sql);
        }
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
//        echo $sql;
        $rs = M()->execute($sql);
        
        $sql = "update lu_advertising_base set comment_count = comment_count + 1  where id = $advId";
        M()->execute($sql);
        return $rs;
    }
    
    /**
     * 
     * 根据广告id获取广告详情
     */
    public function leaveLists($advId, $offset = 0, $pageTotal = 5)
    {
        $sql = "select a.user_id as userId, c.name as userName, c.image as userImage, a.add_time as addTime, a.content, d.name as merchantName, b.content as merchantContent, b.add_time as merchantAddTime from lu_leave_word as a left join lu_leave_word as b on a.id = b.pid left join lu_members as c on a.user_id = c.id left join lu_members as d on b.user_id = d.id where a.pid = 0 and a.adv_id = $advId order by a.add_time desc limit $offset,$pageTotal";
//        echo $sql;
        $rs = M()->query($sql);
        return $rs;
    }
    
    /**
     * 领取红包添加记录
     */
    public function getRedBag($userId, $advId, $money)
    {
        $time = time();
        $sql = "insert into lu_red_bag_log (user_id, adv_id, money, add_time) values($userId, $advId, $money, $time)"; 
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
        $sql = "update lu_members set `total_money` = `total_money` + $money, `money` = `money` + $money where `id` = $userId"; 
        $rs = M()->execute($sql);
        return $rs;
    }
    
    /**
     * 热门搜索标签
     */
    public function hotSearch()
    {
        $sql = "select dataId, type from lu_cate_tags_search_log order by total desc limit 6"; 
        $rs = M()->query($sql);
        if($rs)
        {
            $cate_rs = array();
            $tags_rs = array();
            foreach($rs as $k => $v)
            {
                if($v['type'] == 2)
                {
                    $cate_sql = "select cid as tagId, name as tagName from lu_poster_category where cid = {$v['dataId']}"; 
                    $cate_rs[] = M()->query($cate_sql);
                }else
                {
                    $tags_sql = "select cid as tagId, name as tagName from lu_poster_tags where cid = {$v['dataId']}"; 
                    $tags_rs[] = M()->query($tags_sql);
                }
            }
            $tmp_arr = array_merge($cate_rs, $tags_rs);
            $return = array();
            foreach($tmp_arr as $v)
            {
                $return[] = $v[0];
            }
            return $return;
        }
        return array();
    }
    
    /**
     * 分类列表
     */
    public function cateList()
    {
        $cate_sql = "select cid as categoryId, name as categoryName from lu_poster_category where `status`='1'"; 
        $cate_rs = M()->query($cate_sql);
        return $cate_rs;
    }
    
    /*
     * 获取我的代金券
     * @param int $$userId 会员ID
     * @param int $page 请求的页数
     * @param int $pageSize 每页显示的条数
     * @param int $categoryType 当前状态 1：当前，2：历史
     */
    public function getMyVouchersList($userId,$page,$pageSize,$categoryType)
    {
        $limit = ' limit ' . $page * $pageSize . "," . $pageSize;
        $field = ' b.adv_id as advId, a.vouchers_num as number, a.id as vouchersId, a.add_time as addTime, a.codeImageUrl, b.title, b.number as price, b.type, b.conditions, b.image, a.preferential_id, a.isUse ';
        $where = " where a.user_id = $userId and a.isDel = '2' "; 
        $nowDate = time();
        if($categoryType == 1)
        {
            $where .= " and b.status = '1' and a.isUse = '2' and b.end_date + b.end_time > $nowDate ";
        }else
        {
            $where .= " and (b.status = '2' or a.isUse = '1' or b.end_date + b.end_time <= $nowDate) ";
        }
        $order = ' order by a.add_time desc ';
        $sql = " select $field from lu_vouchers_log as a left join lu_advertising_preferential as b on a.preferential_id = b.id $where $order $limit ";
        $return = M()->query($sql);
//		var_dump($sql,$return);exit;
        if(is_array($return))
        {
            foreach($return as $k => $v)
            {
                $return[$k]['codeImageUrl'] = 'http://' . APP_HOST .'/' . $v['codeImageUrl'];
            }
        }
        
        return $return;
    }
}
