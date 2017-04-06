<?php

namespace Home\Controller;

use Think\Controller;

class InvitationController extends Controller {

    /**
     * 邀请码下载地址
     */
    public function index() {
        $userId = I("get.invitId");
        $this->assign('uid', $userId);
        $this->display();
    }

}
