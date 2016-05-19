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
            $map['is_delete'] = 0;
            if(!in_array($type, array('DUBBING', 'LIPSYNC', 'ORIGINAL'))) {
                $this->error('类型不正确');
            }
            $types = array(
                'DUBBING' => '配音秀',
                'LIPSYNC' => '对口型',
                'ORIGINAL' => '原创'
            );
        } else {
            $map['is_delete'] = 0;
            $types[$type] = '全部';
        }
        
        $Work = M('work');
        $list = $Work->alias('w')
        ->field('w.id,w.uid,w.material_id,w.type,w.video_url,w.cover_url,w.description,d.title,m.nickname')
        ->join('__DOCUMENT__ d on w.material_id = d.id', 'left')
        ->join('__MEMBER__ m on m.uid = w.uid', 'left')
        ->where($map)
        ->order('w.id desc')->select();
        
        //echo $Work->getLastSql();die;
        $this->assign('list', $list);
        $this->assign('type', $types[$type]);
        $this->display();
    }
    
    //设置数据状态
    public function setStatus() {
        $ids    =   I('request.ids', '', 'trim');
        $is_delete =   I('request.is_delete', '', 'intval');
        if(empty($ids)){
            $this->error('请选择要操作的数据');
        }
        if(empty($ids)){
            $this->error('状态为空');
        }
        
        $Work = M('work');
        $map['id'] = array('IN', $ids);
        $data['is_delete'] = $is_delete;
        if($Work->where($map)->save($data)) {
            $this->success('操作成功');
        }
        $this->error('操作失败');
    }
    
    public function recycle() {
        $map['is_delete'] = 1;
        $Work = M('work');
        $list = $Work->alias('w')
        ->field('w.id,w.uid,w.material_id,w.type,w.video_url,w.cover_url,w.description,d.title,m.nickname')
        ->join('__DOCUMENT__ d on w.material_id = d.id', 'left')
        ->join('__MEMBER__ m on m.uid = w.uid', 'left')
        ->where($map)
        ->order('w.id desc')->select();
        
        $this->assign('list', $list);
        $this->display();
    }
}