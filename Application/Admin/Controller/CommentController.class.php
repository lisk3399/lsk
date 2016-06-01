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
        $map['is_delete'] = 0;
        $list = $Comment->alias('c')
        ->field('c.*')
        ->join('__MEMBER__ m on m.uid = c.uid', 'left')
        ->where($map)
        ->order('c.id desc')->select();
        
        $this->assign('list', $list);
        
        $this->display();
    }
}