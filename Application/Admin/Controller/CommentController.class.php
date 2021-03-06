<?php
/**
 * 评论管理
 */
namespace Admin\Controller;
use Admin\Model\AuthGroupModel;
use Think\Page;

class CommentController extends AdminController {

    public function index() {
        $map['c.is_delete'] = 0;
        $list = $this->getCommentList($map);
        
        //内容格式化
        foreach ($list as &$row) {
            $row['content'] = rawurldecode($row['content']);
            $row['create_time'] = date('Y-m-d H:i:s', $row['create_time']);
        }
        $this->assign('list', $list);
        
        $this->display();
    }
    
    public function recycle() {
        $map['c.is_delete'] = 1;
        $list = $this->getCommentList($map);
        
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
    
    /*
     * 获取评论列表
     */
    private function getCommentList($map) {
        $REQUEST = (array)I('request.');
        $page = I('p', '', 'intval');
        //分页配置
        if( isset($REQUEST['r']) ){
            $listRows = (int)$REQUEST['r'];
        }else{
            $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
        }
    
        $Comment = M('Comment');
        $select = $Comment->alias('c')
        ->page($page, $listRows)
        ->field('c.id,c.uid,c.content,c.create_time,m.nickname,d.title,w.description')
        ->join('__WORK__ w on w.id = c.work_id', 'left')
        ->join('__DOCUMENT__ d on d.id = w.material_id', 'left')
        ->join('__MEMBER__ m on m.uid = c.uid', 'left')
        ->where($map)
        ->order('c.id desc');
   
        $list = $select->select();
        foreach ($list as &$row) {
            $row['content'] = rawurldecode($row['content']);
        }
        
        $total = $Comment->alias('c')->where($map)->count();
    
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
    // 修改评论内容
    public function editAction()
    {
        $id=I('get.id');
       empty($id) && $this->error('参数不能为空');
       $data=M('comment')->field(true)->find($id);
      
       $this->assign('data',$data);
       $this->meta_title='修改评论内容';
       $this->display('edit');
    }
    // 编剧评论
    public function saveAction()
    {
        $res = D('comment')->update();
        if(!$res)
        {
            $this->error(D('comment')->getError());

        }
        else{
            $this->success($res['id']?'更新成功!' :'新增成功!' , U('Comment/index'));
        }

    }
   //  //搜素评论内容
   // public function search()
   //  {

   //      $name = I('get.content','','htmlspecialchars');
   //      $where['content'] =['like','%'.$name.'%'];
   //    $list = M('Comment  as  c')
   //    ->join('__MEMBER__  as  m  on  c.uid = m.uid')
   //    ->where($where)->select();  

   //      foreach ($list as &$row) {

   //          $row['content'] = rawurldecode($row['content']);

   //      }

   //     $this->assign('list', $list);
   //     $this->display('index');
   //  }

}