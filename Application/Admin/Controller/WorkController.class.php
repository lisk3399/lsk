<?php
/**
 * 后台作品管理(弃用)
 */
namespace Admin\Controller;
use Admin\Model\AuthGroupModel;
use Think\Page;
use Common\Api\WorkApi;

class WorkController extends AdminController {
    
    public function index() {
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
        //获取作品列表
        $list = $this->getWorkList($map);
        
        $this->assign('list', $list);
        $this->assign('type', $types[$type]);
        $this->display();
    }
    
    //设置数据状态
    public function setStatus() {
        $ids    =   I('request.ids', '', 'trim');
        $is_delete =   I('request.is_delete', '', 'intval');
        $uid = I('uid', '', 'intval');
        $material_id = I('material_id', 0, 'intval');
        if(empty($ids)){
            $this->error('请选择要操作的数据');
        }
        if(empty($ids)){
            $this->error('id为空');
        }
        
        $Work = M('work');
        $map['id'] = array('IN', $ids);
        $data['is_delete'] = $is_delete;
        
        if($Work->where($map)->save($data)) {
            //后台删除则对应用户的作品也需要减少
            if($is_delete == 1) {
                WorkApi::setWorkDec($material_id, $uid);
            }//后台恢复对应用户作品增加
            elseif ($is_delete == 0) {
                WorkApi::setWorksInc($material_id, $uid);
            }
            $this->success('操作成功');
            exit;
        }
        $this->error('操作失败');
    }
    
    //设置是否在首页显示
    public function setDisplay() {
        $ids    =   I('request.ids', '', 'trim');
        $is_display =   I('request.is_display', '', 'intval');
        if(empty($ids)){
            $this->error('请选择要操作的数据');
        }
        if(empty($ids)){
            $this->error('id为空');
        }
    
        $Work = M('work');
        $map['id'] = array('IN', $ids);
        $data['is_display'] = $is_display;
    
        if($Work->where($map)->save($data)) {
            $this->success('操作成功');
            exit;
        }
        $this->error('操作失败');
    }
    
    public function recycle() {
        $map['is_delete'] = 1;
        $list = $this->getWorkList($map);
        
        $this->assign('list', $list);
        $this->display();
    }
    
    /*
     * 获取作品列表
     */
    private function getWorkList($map) {
        $REQUEST = (array)I('request.');
        $page = I('p', '', 'intval');
        //分页配置
        if( isset($REQUEST['r']) ){
            $listRows = (int)$REQUEST['r'];
        }else{
            $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
        }
        
        $Work = M('work');
        $select = $Work->alias('w')
        ->page($page, $listRows)
        ->field('w.id,w.uid,w.material_id,w.type,w.video_url,w.cover_url,w.description,w.is_display,d.title,m.nickname')
        ->join('__DOCUMENT__ d on w.material_id = d.id', 'left')
        ->join('__MEMBER__ m on m.uid = w.uid', 'left')
        ->where($map)
        ->order('w.id desc');
        
        $list = $select->select();
        $total = $Work->alias('w')->where($map)->count();
        
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