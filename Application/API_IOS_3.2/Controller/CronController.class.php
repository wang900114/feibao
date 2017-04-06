<?php

use Think\Controller;

class CronController extends Controller {

    public function _initialize() {
        
    }

    /**
     * 更新后台设置热图
     * 包括图片的中间图
     */
    public function updateAdminHotMap() {
        adminSetHotMap();
    }

    /**
     * 更新数据库中热图设置信息
     */
    public function updateCityHotMapList() {
        set_time_limit(0);
        $data = M("found_hotmap")->where('cityId <>' . 10000)->select();
        if ($data) {
            foreach ($data as $key => $value) {
                getHotMapList($value['cityId']);
                sleep(1);
            }
        }
    }

    /**
     * 更新热图信息的中间图
     */
    public function updateCityHotMap() {
        set_time_limit(0);
        $data = M("found_hotmap")->where('cityId <>' . 10000)->select();
        if ($data) {
            foreach ($data as $key => $value) {
                createCityFoundHotMap($value['cityId']);
                sleep(1);
            }
        }
    }

    /**
     * 后台脚本处理非法用户
     */
    function inquireInvite() {
        D("Invite")->inquireInvite();
    }

}
