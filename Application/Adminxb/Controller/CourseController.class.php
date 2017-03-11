<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Adminxb\Controller;

/**
 * 后台分类管理控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class CourseController extends AdminController {

    /**
     * 分类管理列表
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function index(){
        $tree = D('Course')->getTree(0,'id,title,sort,pid');
        $this->assign('tree', $tree);
        C('_SYS_GET_CATEGORY_TREE_', true); //标记系统获取分类树模板
        $this->meta_title = '分类管理';
        $this->display();
    }

    public function tree($tree = null){
        C('_SYS_GET_CATEGORY_TREE_') || $this->_empty();
        $this->assign('tree', $tree);
        $this->display('tree');
    }

    /* 编辑分类 */
    public function edit(){

            /* 获取分类信息 */
            $data=M('xb_course');
            $sele=$data->where('id='.$_GET['id'])
            ->select();
            $this->assign('lis', $sele);
            $this->meta_title = '编辑分类';
            $this->display('change');
        
    }
    // 编辑分类
    public function change(){
       $id=I('post.id');
       $data['title']=I('post.title');
       $dmodel=M('xb_course')
       ->where('id='.$id)
       ->save($data);
       
       if($dmodel !== false){
             $this->success('更新成功！',U('Course/index'));
        }else{
             $this->error(D('Course')->getError());
        }
    }

    /* 新增分类 */
    public function add($pid = 0){
        $Course = D('Course');
        $title=$_POST['title'];
        if(IS_POST){      //提交表单
            
            if($_POST['pid']===""){
                    $dmodel=M('xb_course');
                    $data=$dmodel->add(array(
                    'title'=>$title,
                    'create_time'=>time(),
                    'status'=>1,
                    ));
                     $this->success('新增成功！', U('index'));
            }else{
                
                    $dmodel=M('xb_course');
                    $data=$dmodel->add(array(
                    'title'=>$title,
                    'create_time'=>time(),
                    'pid'=>$_POST['pid'],
                    'status'=>1,
                    ));
                    $this->success('新增成功！', U('index'));
            }
            
            
        } else {
            $cate = array();
            if($pid){
                /* 获取上级分类信息 */
                $cate = $Course->info($pid, 'id,title,status');
                if(!($cate && 1 == $cate['status'])){
                    $this->error('指定的上级分类不存在或被禁用！');
                }
            }

            /* 获取分类信息 */
            $this->assign('info',       null);
            $this->assign('category', $cate);
            $this->meta_title = '新增课程名';
            $this->display('editadd');
        }
    }

    /**
     * 删除一个分类
     * @author huajie <banhuajie@163.com>
     */
    public function remove(){
        $cate_id = I('id');
        if(empty($cate_id)){
            $this->error('参数错误!');
        }

        //判断该分类下有没有子分类，有则不允许删除
        $child = M('xb_course')->where(array('pid'=>$cate_id))->field('id')->select();
        if(!empty($child)){
            $this->error('请先删除该分类下的子分类');
        }

        //删除该分类信息
        $res = M('xb_course')
        
        ->delete($cate_id);
        if($res !== false){
            //记录行为
            action_log('update_category', 'category', $cate_id, UID);
            $this->success('删除分类成功！');
        }else{
            $this->error('删除分类失败！');
        }
    }

}
