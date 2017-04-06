<?php

namespace Home\Controller;

use Think\Controller;

class TestController extends Controller {

    /**
     * 邀请码下载地址
     */
    function getTest() {
        $time = time();
        qrcode("http://dev.feibaokeji.com/index.php/Home/Test/autoLogin/UUID/" . $time, ROOT . "/Uploads/Temp/123.jpg", 'H', 8);
        $this->assign('imgUrl', "http://dev.feibaokeji.com/Uploads/Temp/123.jpg");
        $this->assign('UUID', $time);
        $this->display();
    }

    function autoLogin() {
        $micode = $_GET['UUID'];
        if (!empty($_GET['UUIDName'])) {
            S("UUID", $micode);
        }
        $this->assign('micode', $micode);
        $this->assign('micode', $micode);
        $this->display();
    }

    function login() {
        $micode = $_GET['UUID'];
        //var_dump(S("UUID"));
        if ($micode == S("UUID")) {
            $array = array('error' => 100);
        }

        echo jsonStr($array);
    }

}
