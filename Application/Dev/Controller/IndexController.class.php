<?php
use Think\Controller;

class IndexController extends Controller {
    public function index() {
    	$this->navMenu = C ('navMenu');
    	$this->assign('groups', $this->navMenu);
    	$this->display();
    }

    public function test() {
    	header("Content-type: text/html; charset=utf-8");
    	echo "北京 ：". getProvinceIdById(1) . getCityName(getProvinceIdById(1)) ."<hr>\r\n";
    	echo "天津 ：". getProvinceIdById(2) . getCityName(getProvinceIdById(2)) ."<hr>\r\n";
    	echo "唐山市 ：". getProvinceIdById(40) . getCityName(getProvinceIdById(40)) ."<hr>\r\n";
    	echo "邯郸市 ：". getProvinceIdById(42) . getCityName(getProvinceIdById(42)) ."<hr>\r\n";
    	echo "桥东区 ：". getProvinceIdById(418) . getCityName(getProvinceIdById(418)) ."<hr>\r\n";
    	echo "新华区 ：". getProvinceIdById(420) . getCityName(getProvinceIdById(420)) ."<hr>\r\n";
    }

}