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
        //用户增加班级属性
        //
    }
    
    /**
     * 搜索班级
     */
    public function searchClass() {
        
    }
    
}
