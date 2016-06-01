<?php
namespace Home\Controller;
use Think\Controller;

/**
 * 前台公共控制器
 * 为防止多分组Controller名称冲突，公共Controller名称统一使用分组名称
 */
class MobileController extends Controller {
    public function index() {
    }
    
    public function share() {
        $this->display();
    }
}
