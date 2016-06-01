<?php
/**
 * 评论管理
 */
namespace Admin\Controller;
use Admin\Model\AuthGroupModel;
use Think\Page;

class CommentController extends AdminController {

    public function index() {
        $Comment = M('Comment');
        $where = 'c.is_delete = 0';
        $list = $Comment->alias('c')
        ->field('c.id,c.to_uid,c.create_time,m.nickname,d.title,w.description')
        ->join('__WORK__ w on w.id = c.work_id', 'left')
        ->join('__DOCUMENT__ d on d.id = w.material_id', 'left')
        ->join('__MEMBER__ m on m.uid = c.uid', 'left')
        ->where($where)
        ->order('c.id desc')->select();
        
        //内容格式化
        foreach ($list as &$row) {
            $row['content'] = rawurldecode($row['content']);
            $row['create_time'] = date('Y-m-d H:i:s', $row['create_time']);
        }
        $this->assign('list', $list);
        
        $this->display();
    }
    
    public function recycle() {
        $Comment = M('Comment');
        $where = 'c.is_delete = 1';
        $list = $Comment->alias('c')
        ->field('c.id,c.to_uid,c.create_time,m.nickname,d.title,w.description')
        ->join('__WORK__ w on w.id = c.work_id', 'left')
        ->join('__DOCUMENT__ d on d.id = w.material_id', 'left')
        ->join('__MEMBER__ m on m.uid = c.uid', 'left')
        ->where($where)
        ->order('c.id desc')->select();
        
        //内容格式化
        foreach ($list as &$row) {
            $row['content'] = rawurldecode($row['content']);
            $row['create_time'] = date('Y-m-d H:i:s', $row['create_time']);
        }
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
        $Comments = M('comment');
        $map['id'] = array('IN', $ids);
        $data['is_delete'] = $is_delete;
        if($Comments->where($map)->save($data)) {
            $this->success('操作成功');exit;
        }
        $this->error('操作失败');
    }
}