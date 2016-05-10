<?php
/**
 * Class ClassesController
 * @name 班级相关接口
 */

namespace Home\Controller;

/**
 * 班级控制器
 */
class ClassesController extends HomeController {

	public function index(){
	}
	
	/**
	 * 创建班级
	 */
    public function createClass() {
        if(IS_POST) {
            $uid = is_login();
            if(!$uid) {
                $this->renderFailed('请先登录');
            }
            $class_name = I('class_name', '', 'trim');
            if(empty($class_name)) {
                $this->renderFailed('请输入班级名');
            }
            //检查班级是否存在
            $map['class'] = $class_name;
            $Classes = M('classes');
            $info = $Classes->field('id,province,city,district,school,class')->where($map)->find();
            if($info['id']) {
                $this->renderSuccess('已经存在', $info);
            }
            else {
                $map['uid'] = $uid;
                $ret = $Classes->add($map);
                if($ret) {
                    $this->renderSuccess('创建成功，我们会对学校进行审核');
                } else {
                    $this->renderFailed('创建失败请重试');
                }
            }
        }
    }
    
    /**
     * 加入班级
     */
    public function joinClass() {
        if(IS_POST) {
            $uid = is_login();
            if(!$uid) {
                $this->renderFailed('请先登录');
            }
            //是否已经有班级
            $class_id = $this->isJoin($uid);
            if($class_id) {
                $class_info = $this->getByClassId($class_id);
                $this->renderFailed('您已经加入过'.$class_info['class']);
            }
            
            //加入某班级
            $class_id = I('class_id', '', 'intval');
            if(empty($class_id)) {
                $this->renderFailed('班级id为空');
            }
            //用户未加入该班级
            if(!$this->isJoinClass($uid, $class_id)) {
                $data['uid'] = $uid;
                $data['classid'] = $class_id;
                M('member')->save($data);
                $this->renderFailed('加入成功');
            } else {
                $this->renderFailed('您已经加入过该班级');
            }
        }
    }
    
    /**
     * 搜索班级
     */
    public function searchClass() {
        
    }
    
    /**
     * 检查是否加入班级
     */
    private function isJoinClass($uid, $class_id) {
        $map['uid'] = $uid;
        $map['classid'] = $class_id;
        $info = M('member')->field('uid')->where($map)->find();
        if(!$info['uid']) {
            return false;
        }
        return true;
    }
    
    /**
     * 检查是否已经有班级
     */
    private function isJoin($uid) {
        $map['uid'] = $uid;
        $info = M('member')->field('classid')->where($map)->find();
        
        if(!$info['classid']) {
            return false;
        }
        return $info['classid'];
    }
    
    /**
     * 通过id获取班级
     * @param unknown $class_id
     */
    private function getByClassId($class_id) {
        $Classes = M('classes');
        $map['id'] = $class_id;
        return $Classes->where($map)->find();
    }
}