<?php
/**
 * 班级管理
 */
namespace Admin\Controller;
use Admin\Model\AuthGroupModel;
use Think\Page;

class ClassesController extends AdminController {

    public function index() {
        $Classes = M('Classes');
        $map['is_delete'] = 0;
        $list = $Classes->alias('c')
        ->field('c.id,c.uid,c.province,c.city,c.district,c.school,c.class,m.nickname')
        ->join('__MEMBER__ m on m.uid = c.uid', 'left')
        ->where($map)
        ->order('c.id desc')->select();
        
        $this->assign('list', $list);
        
        $this->display();
    }
    
    public function recycle() {
        $Classes = M('Classes');
        $map['is_delete'] = 1;
        $list = $Classes->alias('c')
        ->field('c.id,c.uid,c.province,c.city,c.district,c.school,c.class,m.nickname')
        ->join('__MEMBER__ m on m.uid = c.uid', 'left')
        ->where($map)
        ->order('c.id desc')->select();
        
        $this->assign('list', $list);
        
        $this->display();
    }
    
    public function setStatus() {
        $ids    =   I('request.ids', '', 'trim');
        $is_delete =   I('request.is_delete', '', 'intval');
        if(empty($ids)){
            $this->error('请选择要操作的数据');
        }
        if(empty($ids)){
            $this->error('id为空');
        }
        $Classes = M('classes');
        $map['id'] = array('IN', $ids);
        $data['is_delete'] = $is_delete;
        if($Classes->where($map)->save($data)) {
            $this->success('操作成功');exit;
        }
        $this->error('操作失败');
    }
}