<?php
/**
 * 班级管理
 */
namespace Admin\Controller;
use Admin\Model\AuthGroupModel;
use Think\Page;

class ClassesController extends AdminController {

    public function index() {
        $map['is_delete'] = 0;
        $list = $this->getClassList($map);
        
        $this->assign('list', $list);
        $this->display();
    }
    
    public function recycle() {
        $map['is_delete'] = 1;
        $list = $this->getClassList($map);
        
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
    
    /*
     * 获取班级列表
     */
    private function getClassList($map) {
        $REQUEST = (array)I('request.');
        $page = I('p', '', 'intval');
        //分页配置
        if( isset($REQUEST['r']) ){
            $listRows = (int)$REQUEST['r'];
        }else{
            $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
        }
    
        $Classes = M('Classes');
        $select = $Classes->alias('c')
        ->page($page, $listRows)
        ->field('c.id,c.uid,c.province,c.city,c.district,c.school,c.class,m.nickname')
        ->join('__MEMBER__ m on m.uid = c.uid', 'left')
        ->where($map)
        ->order('c.id desc');
    
        $list = $select->select();
        $total = $Classes->alias('c')->where($map)->count();
        
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if($total>$listRows){
            $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p =$page->show();
    
        $this->assign('_page', $p? $p: '');
        $this->assign('_total',$total);
        $options['limit'] = $page->firstRow.','.$page->listRows;
    
        return $list;
    }
}