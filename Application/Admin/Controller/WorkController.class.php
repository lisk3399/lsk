<?php
/**
 * 后台作品管理
 */
namespace Admin\Controller;
use Admin\Model\AuthGroupModel;
use Think\Page;

class WorkController extends AdminController {
    
    public function index() {
        //$this->getMenu();
        $type = I('type', '', 'trim');
        if(!empty($type)) {
            $map['w.type'] = $type;
            if(!in_array($type, array('DUBBING', 'LIPSYNC', 'ORIGINAL'))) {
                $this->error('类型不正确');
            }
            $types = array(
                'DUBBING' => '配音秀',
                'LIPSYNC' => '对口型',
                'ORIGINAL' => '原创'
            );
        } else {
            $map = '1 = 1';
        }
        $Work = M('work');
        $list = $Work->alias('w')
        ->field('w.id,w.uid,w.material_id,w.type,w.video_url,w.cover_url,w.description,d.title,m.nickname')
        ->join('__DOCUMENT__ d on w.material_id = d.id', 'left')
        ->join('__MEMBER__ m on m.uid = w.uid', 'left')
        ->where($map)
        ->order('w.id desc')->select();
        
        $this->assign('list', $list);
        $this->assign('type', $types[$type]);
        $this->display();
    }
    
    public function type() {
        
    }
    
    /**
     * 显示左边菜单，进行权限控制
     * @author huajie <banhuajie@163.com>
     */
    protected function getMenu(){
        //获取动态分类
        $cate_auth  =   AuthGroupModel::getAuthCategories(UID); //获取当前用户所有的内容权限节点
        $cate_auth  =   $cate_auth == null ? array() : $cate_auth;
        $cate       =   M('Category')->where(array('status'=>1))->field('id,title,pid,allow_publish')->order('pid,sort')->select();
    
        //没有权限的分类则不显示
        if(!IS_ROOT){
            foreach ($cate as $key=>$value){
                if(!in_array($value['id'], $cate_auth)){
                    unset($cate[$key]);
                }
            }
        }
    
        $cate           =   list_to_tree($cate);    //生成分类树
    
        //获取分类id
        $cate_id        =   I('param.cate_id');
        $this->cate_id  =   $cate_id;
    
        //是否展开分类
        $hide_cate = false;
        if(ACTION_NAME != 'recycle' && ACTION_NAME != 'draftbox' && ACTION_NAME != 'mydocument'){
            $hide_cate  =   true;
        }
    
        //生成每个分类的url
        foreach ($cate as $key=>&$value){
            $value['url']   =   'Article/index?cate_id='.$value['id'];
            if($cate_id == $value['id'] && $hide_cate){
                $value['current'] = true;
            }else{
                $value['current'] = false;
            }
            if(!empty($value['_child'])){
                $is_child = false;
                foreach ($value['_child'] as $ka=>&$va){
                    $va['url']      =   'Article/index?cate_id='.$va['id'];
                    if(!empty($va['_child'])){
                        foreach ($va['_child'] as $k=>&$v){
                            $v['url']   =   'Article/index?cate_id='.$v['id'];
                            $v['pid']   =   $va['id'];
                            $is_child = $v['id'] == $cate_id ? true : false;
                        }
                    }
                    //展开子分类的父分类
                    if($va['id'] == $cate_id || $is_child){
                        $is_child = false;
                        if($hide_cate){
                            $value['current']   =   true;
                            $va['current']      =   true;
                        }else{
                            $value['current']   =   false;
                            $va['current']      =   false;
                        }
                    }else{
                        $va['current']      =   false;
                    }
                }
            }
        }
        $this->assign('nodes',      $cate);
        $this->assign('cate_id',    $this->cate_id);
    
        //获取面包屑信息
        $nav = get_parent_category($cate_id);
        $this->assign('rightNav',   $nav);
    
        //获取回收站权限
        $this->assign('show_recycle', IS_ROOT || $this->checkRule('Admin/article/recycle'));
        //获取草稿箱权限
        $this->assign('show_draftbox', C('OPEN_DRAFTBOX'));
        //获取审核列表权限
        $this->assign('show_examine', IS_ROOT || $this->checkRule('Admin/article/examine'));
    }
}