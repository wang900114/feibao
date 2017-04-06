<?php

use Think\Controller;
/**
 * 公共模块
 * @author Jine <luxikun@andlisoft.com>
 */
class TestController extends Controller {

    public function showMassage(){
        $massage = "官人,您的版本太老了,很多功能都无法使用了!赶快升级吧!";
        $uid = 43146;
        pushMassageAndWriteMassage($massage, $uid);
    }
    
    public function show(){
        $this->calculateUserShareIntegral(43146, 1659);
    }
    
    public function calculateUserShareIntegral($uid, $pid) {
        //每张海报每个人可获得的最大分享飞币数
        $posters_share_max_integral = D("System")->readConfig("posters_share_max_integral");
        //每张海报每个人可在最多前多少次获得飞币
        $posters_share_max_times = D("System")->readConfig("posters_share_max_times");
        //统计当前用户对当前海报分享了多少次
        $where = array();
        $where['dataId'] = $pid;
        $where['userId'] = $uid;
        $m = M("share_poster_log");
        $share_count = $m->where($where)->count("id");
        //分享第十次之后不再获得飞币
        if ($share_count == $posters_share_max_times) {
            return 0;
        }
        //分享第一次到第四次的区间范围的最大值
        if ($share_count) {
            $max_integral = $posters_share_max_integral - M("share_poster_log")->where($where)->sum('integral');
        } else {
            $max_integral = $posters_share_max_integral - 10;
        }
        
        //使用放大区间并求余的方式让随机值更散列
//        $mt_rand = mt_rand(0, 2100000000);
//        $integral = $mt_rand % $max_integral;
        //或者采用直接的随机值
        $integral = mt_rand(0, $max_integral);
        return $integral;
    }
    
    function test(){
        $userId = 54806;
        $address = "北京市朝阳区朝阳路";
        $id = 1298;
        echo 111;exit;
        $aid = D("Poster")->updataPostLimitLog($id, $address, $userId);
        showTestInfo($aid);
    }

}
