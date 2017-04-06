<?php

/**
 * 城市数据接口
 * @author FrankKung <kongfanjian@andlisoft.com>
 */
class CityController extends BaseController {

    public function _initialize() {
        // parent::_initialize();
        //A('API_3.2/Public')->testPublicToken();//验证 公共 token
    }

    /**
     * 返回更新后的城市数据
     * @access public
     * @param string lastUpdateTime
     * @author FrankKung <kongfanjian@andlisoft.com>
     */
    public function getupdateCity() {
        if (is_empty(I('post.updateTime'))) {//判断参数是否为空
            $ret['status'] = 10;
            $ret['message'] = '操作失败';
        } else {
            $updateTime = I('request.updateTime', '', 'intval');
            //$serverUpdateTime = D('Common')->getConfig('city_update_time');

            //if ($clientUpdateTime >= $serverUpdateTime) {//判断添加时间是否正确
                //$this->ret['status'] = 10;
                //$this->ret['message'] = '操作失败';
                //$this->ret['info']['data'] = array();
            //} else {
            $city = D('City')->getUpdateCity($updateTime);
            if (!empty($city)) {//判断城市信息是否为空
                $this->ret['status'] = 1;
                $this->ret['message'] = '查询成功';
                $this->ret['info']['nowUpdateTime'] = time();
                $this->ret['info']['data'] = $city;
            } else if (is_bool($city) && empty($city)) {
                $this->ret['status'] = -1;
                $this->ret['message'] = '查询失败';
                $this->ret['info']['data'] = array();
            } else if ((is_array($city) || is_null($city)) && empty($city)) {
                $this->ret['status'] = 36;
                $this->ret['info']['nowUpdateTime'] = time();
                $this->ret['message'] = '查询成功，暂无数据';
                $this->ret['info']['data'] = array();
            }
            //}
        }

        $this->apiCallback($this->ret);exit();
    }

}
