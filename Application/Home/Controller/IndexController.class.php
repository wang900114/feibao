<?php

namespace Home\Controller;

use Think\Controller;

class IndexController extends Controller {

    /**
     * 发现分享详情
     */
    public function foundShare() {
        $where['id'] = base64_decode(I('get.id'));
        $info = D('Found')->where($where)->find();
        $info['content'] = jsonStrWithOutBadWordsNew($info['content']);
        $data = M("picture_found")->where("dataId=" . $where['id'])->field("image")->select();
        //var_dump(APP_DEBUG);die;
        
        if ($data) {
            if ($info['content']) {
                $info['content'] = str_replace(array("\r\n","\r","\n", '"'), array("","","", "'"), $info['content']);
                $contentStr .= '"' . htmlspecialchars_decode($info['content']) . '",'; //去实体化
            }
            foreach ($data as $key => $value) {
                $imageStr .= '<li data-src="' . $value['image'] . '" style="width: 1879px;"></li>';
            }
            $titles = empty($info['title']) ? str_cut($info['content'], 15, '...') : $info['title'];
            $contentStr = trim($contentStr, ',');
            $total = count($data);
            $this->assign("titles", $titles);
            $this->assign("contentStr", $contentStr);
            $this->assign("imageStr", $imageStr);
            $this->assign("total", $total);
            $this->display("sharePath");exit(0);
        } else {
            $this->assign("info", $info);
            $this->display("sharePathNoPic");exit(0);
        }
    }
    
    /**
     * 3.3分享入口（显示静态页面）
     */
    public function shareHtml()
    {
        $html = $_GET['html'];
//        echo $html;exit;
        $this->display("Advertising/{$html}");exit(0);
    }
    
    /**
     * 3.3广告详情分享
     */
    public function advertShare() {
        $advId = I('post.advId');
        if (is_empty($advId)) {//判断参数是否完整
            $return['status'] = -888;
            $return['message'] = '传参不完整';
        }
        $sql = "select a.id, a.shareUrl, a.status, a.title, a.user_id userId, b.image userImage, a.comment_count commentsTotal, a.interest_count interestTotal, a.collection_count collectionTotal, a.image advertisingImages, a.tel, a.web_url webUrl, a.address, a.content, a.red_number redNumber, a.red_remarks redRemarks, a.add_time as addTime from lu_advertising_base a left join lu_members b on a.user_id = b.id where a.id = {$advId} and a.status = '2'";
        $detail = M()->query($sql);
        
        $info = $detail[0];
        $info['userId'] = encodePass($info['userId']);
        $info['isCollection'] = 2;
        $info['isInterest'] = 2;
        //分类
        $cate_sql = "select b.name from lu_poster_type_relation_new as a left join lu_poster_category as b on a.typeId = b.cid where a.categoryType = '2' and a.dataId = {$advId}";
        $tags_sql = "select b.name from lu_poster_type_relation_new as a left join lu_poster_tags as b on a.typeId = b.cid where a.categoryType = '3' and a.dataId = {$advId}";
        $cate_rs = M()->query($cate_sql);
        $tags_rs = M()->query($tags_sql);  
        $info['category'] = $cate_rs && $tags_rs ? array_merge($cate_rs, $tags_rs) :array();
        
        $sql = "select id, name, price, image, add_time as addTime from lu_commodity where status = '1' and adv_id = {$advId} order by add_time desc"; 
        $info['goodsInfo'] = M()->query($sql);
        
        foreach($info['goodsInfo'] as $k => $v)
        {
            if($v['price'])
            {
                $info['goodsInfo'][$k]['price'] = $this->fenZhuanYuan($v['price']);
            }
        }
        
        $sql = "select id, title name, number as price, image, add_time as addTime, type from lu_advertising_preferential where status = '1' and adv_id = {$advId} order by add_time desc"; 
        $info['favorableInfo'] = M()->query($sql);
        foreach($info['favorableInfo'] as $k => $v)
        {
            if($v['price'] && $v['type'] == 2)
            {
                $info['favorableInfo'][$k]['price'] = $this->fenZhuanYuan($v['price']);
            }
        }
        
        $sql = "select id, name, price, image, add_time as addTime from lu_service where status = '1' and adv_id = {$advId} order by add_time desc"; 
        $info['serviceInfo'] = M()->query($sql);
        foreach($info['serviceInfo'] as $k => $v)
        {
            if($v['price'])
            {
                $info['serviceInfo'][$k]['price'] = $this->fenZhuanYuan($v['price']);
            }
        }
        
        $return['status'] = 1;
        $return['message'] = '';
        $return['success'] = true;
        $return['info'] = $info;
//        var_dump($return);
        echo jsonStr($return);exit();
    }

    
    /**
     * 服务、商品、优惠代金券详情接口
     * 
     */
    public function zhDetailShare()
    {
        $advId = I('post.advId');
        $type = I('post.type');
        switch($type)
        {
            case 1:
                $tableName = 'lu_service';
                $field = " *, name as title";
                break;
            case 2:
                $tableName = 'lu_commodity';
                $field = " *, name as title";
                break;
            case 3:
                $tableName = 'lu_advertising_preferential';
                $field = "*, number as price";
                break;
        }
        $sql = "SELECT $field FROM `$tableName` WHERE ( id=$advId ) LIMIT 1";
        $info = M()->query($sql);
        if($info && is_array($info)){
            $info = $info[0];
            if($info['price'] && $info['type'] != 1)
            {
                $info['price'] = $this->fenZhuanYuan($info['price']);
            }

            $return['info']['type'] = $type; 
            $return['info']['shareUrl'] = $type; 
            $return['info']['image'] = $info['image']; 
            $return['info']['title'] = $info['title']; 
            $return['info']['price'] = $info['price']; 
            $return['info']['content'] = $info['content']; 
            $return['info']['addTime'] = $info['add_time'];

            //优惠/代金券
            if($type == 3)
            {
                $return['info']['startDay'] = $info['start_date']; 
                $return['info']['endDay'] = $info['end_date']; 
                $return['info']['startTime'] = floor($info['start_time'] / 3600) . ":" . ($info['start_time'] % 3600) / 60; 
                $return['info']['endTime'] = floor($info['end_time'] / 3600) . ":" . ($info['end_time'] % 3600) / 60; 
                $return['info']['isAllDay'] = $info['is_all_day']; 
                $return['info']['method'] = $info['type']; 
                $return['info']['conditionsUse'] = $info['conditions']; 

                $sql = " select count(*) as count from lu_vouchers_log where user_id = $user and preferential_id = {$info['id']} ";
                $isReceive = M()->query($sql);

                $return['info']['isReceive'] = $isReceive[0]['count'] > 0 ? 1 : 2;                     
            }

            $return['status'] = 1;
            $return['message'] = '查询成功';
        } else {
            $return['status'] = 10;
            $return['message'] = '查询成功，暂无数据';
        }
        header("Content-Type: application/json; charset=utf-8");
        echo jsonStr($return);exit;
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
