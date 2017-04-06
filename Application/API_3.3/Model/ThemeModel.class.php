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
     * 根据广告id获取标签列表
     */
    public function lists($offset, $pageTotal)
    {
        $order = ' order by id desc ';
        $limit = " limit $offset,$pageTotal ";
        $sql = ""; 
        $rs = M()->query($sql);
        return $rs;
    }
    
    /**
     * 
     * 根据广告id获取标签列表
     */
    public function detail($themeId)
    {
        $tableName = '';
        $sql = "select a.id, a.title, a.add_time as addTime, a.content, a.name as userName from $tableName as a left join lu_members on a.user_id = b.id where a.id = $themeId "; 
        $rs = M()->query($sql);
        return $rs;
    }
    
    
}