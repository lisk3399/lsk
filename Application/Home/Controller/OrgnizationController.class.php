<?php
/**
 * Class OrgnizationController
 * @name 机构管理
 */

namespace Home\Controller;

use User\Api\UserApi;
class OrgnizationController extends HomeController {
    //我创建的机构
    public function myOrg() {
        
    }
    
    //我加入的机构列表
    public function orgJoined() {
        
    }
    
    //创建机构
    public function createOrg() {
        //只能创建一个机构
        if(IS_POST) {
            $uid = is_login();
            if(empty($uid)) {
                $this->renderFailed('需要登录', -1);
            }
            $name = I('post.name', '', 'trim');
            if(empty($name)) {
                $this->renderFailed('机构名为空');
            }
            $cover_url = I('post.cover_url', '', 'trim');
            if(empty($cover_url)) {
                $this->renderFailed('机构图片为空');
            }
            //创建机构字符限制
            $title_len = mb_strlen($name, 'utf-8');
            if($title_len>30 || $title_len<=2) {
                $this->renderFailed('机构名为2-30个字');
            }
            //群组名是否存在
            if($this->checkOrgExists($name)) {
                $this->renderFailed('已存在该机构');
            }
            //创建群组数量限制
            if($this->checkOrgNum($uid) >= 1) {
                $this->renderFailed('您最多只能创建1个机构');
            }
            $data['uid'] = $uid;
            $data['name'] = $name;
            $data['is_delete'] = 0;
            $data['cover_url'] = $cover_url;
            $data['create_time'] = NOW_TIME;
             
            $Group = M('orgnization');
            $group_id = $Group->add($data);
             
            if($group_id) {
                $this->renderSuccess('创建成功');
            }
            $this->renderFailed('创建失败');
        }
    }
    
    //检查机构id是否存在
    public function checkOrgIdExists($org_id) {
        $Org = M('orgnization');
        $map['id'] = $org_id;
        return $Org->where($map)->find();
    }
    
    /**
     * 检查机构名是否存在
     */
    private function checkOrgExists($name) {
        $Org = M('orgnization');
        $map['name'] = $name;
        return $Org->where($map)->find();
    }
    
    /**
     * 检查用户创建机构数量
     */
    private function checkOrgNum($uid) {
        $Org = M('orgnization');
        $map['uid'] = $uid;
        $map['is_delete'] = 0;
        return $Org->where($map)->count();
    }
}	
