<?php
/**
 * Class ActivityController
 * @name 活动管理
 */

namespace Home\Controller;

class ActivityController extends HomeController {

	public function index(){
	}
	
	/**
	 * 获取某活动的作品列表
	 */
    public function getActivityWork() {
        $page = I('page', '1', 'intval');
        $rows = I('rows', '20', 'intval');
        
        //限制单次最大读取数量
        if($rows > C('API_MAX_ROWS')) {
            $rows = C('API_MAX_ROWS');
        }
        
        $activity_id = I('activity_id', '', 'intval');
        if(empty($activity_id)) {
            $this->renderFailed('id为空');
        }
        
        $Activity = M('activity');
        $list = $Activity->page(page, rows)
        ->where()
        ->order('id desc')
        ->select();
        
        print_r($list);
    }
    
    /**
     * 活动报名功能
     */
    public function signup() {
        if(IS_POST) {
            $uid = is_login();
            if(empty($uid)) {
                $this->renderFailed('请先登录');
            }
            
            $activity_id = I('activity_id', '', 'intval');
            if(empty($activity_id)) {
                $this->renderFailed('未指定参加的活动');
            }
            //活动是否存在
            if(!$this->checkActivityExist($activity_id)) {
                $this->renderFailed('活动不存在');
            }
            //已经报过名
            if($this->isSignup($uid, $activity_id)) {
                $this->renderFailed('您已经报名参加过该活动');
            }
            $name = I('name', '', 'trim');
            if(empty($name)) {
                $this->renderFailed('请填写姓名');
            }
            $gender = I('gender', '', 'intval');
            if(empty($gender)) {
                $this->renderFailed('请选择性别');
            }
            $age = I('age', '', 'trim');
            if(empty($age)) {
                $this->renderFailed('请填写年龄');
            }
            $mobile = I('mobile', '', 'trim');
            if(empty($mobile)) {
                $this->renderFailed('请填写联系电话');
            }
            $intro = I('intro', '', 'trim');
            $create_time = NOW_TIME;
            
            $Signup = M('signup');
            $data['activity_id'] = $activity_id;
            $data['uid'] = $uid;
            $data['name'] = $name;
            $data['gender'] = $gender;
            $data['age'] =$age;
            $data['mobile'] = $mobile;
            $data['intro'] = $intro;
            $data['create_time'] = $create_time;
            
            if($Signup->add($data)) {
                $this->renderSuccess('报名成功');
            } 
            
            $this->renderFailed('报名失败，请稍后重试');
        }
    }
    
    /**
     * 检查活动是否存在
     */
    private function checkActivityExist($activity_id) {
        $Activity = M('activity');
        $map['id'] = $activity_id;
        $ret = $Activity->where($map)->find();
        if($ret['id']) {
            return true;
        }
        return false;
    }
    
    /**
     * 用户是否报名检查
     */
    private function isSignup($uid, $activity_id) {
        $Signup = M('signup');
        $map['activity_id'] = $activity_id;
        $map['uid'] = $uid;
        $ret = $Signup->where($map)->find();
        if($ret['id']) {
            return true;
        }
        return false;
    }
    
    /**
     * 检查用户是否参与过活动
     */
    public function checkSignup() {
        if(IS_POST) {
            $uid = is_login();
            if(empty($uid)) {
                $this->renderFailed('请先登录');
            }
            
            $activity_id = I('activity_id', '', 'intval');
            if(empty($activity_id)) {
                $this->renderFailed('未指定参加的活动');
            }
            //活动是否存在
            if(!$this->checkActivityExist($activity_id)) {
                $this->renderFailed('活动不存在');
            }
            
            if($this->isSignup($uid, $activity_id)) {
                $this->renderFailed('您已经报名该活动');
            }
            $this->renderSuccess('您未报名');
        }
    }
    
    /**
     * 活动是否需要报名
     */
    public function needSignup() {
        $activity_id = I('activity_id', '', 'intval');
        if(empty($activity_id)) {
            $this->renderFailed('未指定参加的活动');
        }
        //活动是否存在
        if(!$this->checkActivityExist($activity_id)) {
            $this->renderFailed('活动不存在');
        }
        $Activity = M('activity');
        $map['id'] = $activity_id;
        $ret = $Activity->where($map)->find();
        if($ret['is_need_signup']) {
            $this->renderSuccess('需要报名');
        }
        $this->renderFailed('不需要报名');
    }
}
