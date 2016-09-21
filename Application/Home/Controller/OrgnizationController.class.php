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
        $uid = is_login();
        if(!empty($uid)) {
            $Org = M('orgnization');
            $map['uid'] = $uid;
            $map['is_delete'] = 0;
            $list = $Org->field('id,uid,name,cover_url')
            ->where($map)->order('id desc')->select();
             
            if(count($list) == 0) {
                $this->renderFailed('没有更多了');
            }
            
            $this->renderSuccess('我创建的机构', $list);
        }
        //未登录不显示
        $this->renderFailed('没有更多了');
    }
    
    //我加入的机构列表
    public function orgJoined() {
        $uid = is_login();
        if(!empty($uid)) {
            $page = I('page', '1', 'intval');
            $rows = I('rows', '20', 'intval');
             
            //限制单次最大读取数量
            if($rows > C('API_MAX_ROWS')) {
                $rows = C('API_MAX_ROWS');
            }
            
            $member_org = M('member_org');
            $map['uid'] = $uid;
            $mo_list = $member_org->field('org_id')
            ->where($map)->order('id desc')->select();
            
            if(count($mo_list) == 0) {
                $this->renderFailed('没有更多了');
            }
            
            $org_ids = array();
            foreach ($mo_list as $row) {
                $org_ids[] = $row['org_id'];
            }
        
            $map['is_delete'] = 0;
            $map['uid'] = array('NEQ', $uid);
            $map['id'] = array('IN', $org_ids);
            
            $Org = M('orgnization');
            $list = $Org->field('id,uid,name,cover_url')
            ->page($page, $rows)
            ->where($map)
            ->order('id desc')
            ->select();
            
            if(count($list) == 0) {
                $this->renderFailed('没有更多了');
            }
            $this->renderSuccess('我创建的机构', $list);
        }
        //未登录不显示
        $this->renderFailed('没有更多了');
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
                //创建机构后自己也关注
                $map['uid'] = $uid;
                $map['org_id'] = $group_id;
                $map['create_time'] = NOW_TIME;
                if(M('member_org')->add($map)) {
                    $this->renderSuccess('创建成功');
                }
                $this->renderFailed('创建失败');
            }
            $this->renderFailed('创建失败');
        }
    }
    
    //搜索机构
    public function searchOrg() {
        
    }
    
    //加入/关注机构
    public function joinOrg() {
        
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
