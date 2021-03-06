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
    
    public function setStatus(){
        $ids    =   I('request.ids', '', 'trim');
        $data['is_delete'] =   I('request.is_delete', '', 'intval');
        if(empty($data)){
            $this->error('请选择要操作的数据');
        }
        if(empty($ids)){
            $this->error('id为空');
        }
        $Classes = M('group');
        $map['id'] = array('IN', $ids); 
        if($Classes->where($map)->save($data)) {
            $this->success('操作成功');exit;
        }
        $this->error('操作失败');
    }
//获取班级列表
    public function getClassList($map){
        $REQUEST = (array)I('request.');
        $page = I('p', '', 'intval');
        //分页配置
        if( isset($REQUEST['r']) ){
            $listRows = (int)$REQUEST['r'];
        }else{
            $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
        }

        $Classes = M('group');
        $select = $Classes->alias('c')
        ->page($page, $listRows)
        ->field('c.id,c.uid,c.group_name,c.is_delete,m.nickname,a.name')
        ->join('__MEMBER__ m on m.uid = c.uid', 'left')
        ->join('__ORGNIZATION__ a on c.uid = a.uid', 'left')
        ->where('c.is_delete=0')
        ->order('c.id desc')
        ->select();

        $total = $Classes->alias('c')->where($map)->count();
        $page = new \Think\Page($total, $listRows, $REQUEST);
        if($total>$listRows){
            $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p =$page->show();
        $this->assign('_page', $p? $p: '');
        $this->assign('_total',$total);
        $options['limit'] = $page->firstRow.','.$page->listRows;
        return $select;
    }
// 编辑班级
    public function editAction(){
        // 接受数据
        $id=I('get.id');
        empty($id) && $this->error('参数不能为空');
        $data=M('Group')->field(true)->find($id);
        $this->assign('data',$data);
        $this->meta_title='编辑班级';
        $this->display('edit');
    }
    //修改班级名称
    public function saveAction(){
        header("Content-Type:text/html; charset=utf-8");
        $Dao = M("Group");
        $result = $Dao->where('id ='. $_POST['id'])
                     ->setField('group_name',$_POST['group_name']);
        if($result !== false){
             $this->success($result['group_name']?'更新成功！':'新增成功！',U('Classes/index'));
        }else{
             $this->error(D('Classes')->getError());
        }
    }
    // 获取班级下成员
    public function classe()
    {
        $id = I('get.cate_id');
        $Classes = D('member_group');
        $list = $Classes->alias('c')
        ->field('a.id,a.uid,a.group_name,b.uid,b.classid,b.classid,b.nickname,c.group_id')
        ->join('__GROUP__ a ON  c.group_id=a.id')
        ->join('__MEMBER__ b ON b.uid=c.uid')
        ->where($id.'=c.group_id' )
        ->select(); 
        $this->assign('list',$list);
        $this->display('classe');
    }
    //班级搜索
    public function search()
    {
       $name = I('get.group_name','','htmlspecialchars');
       $where['group_name'] =array('like','%'.$name.'%');
       $list = M('_group  as  c')
       ->join('__MEMBER__  as  m  on  c.uid = m.uid')
       ->where($where  )
       ->where('is_delete=0')
       ->select();  

       $this->assign('list', $list);
       $this->display('index');
    }
    // 添加班级成员
       public function addAction(){
        $GLOBALS['classid'] = I('get.id');
        $this->meta_title = '新增成员';
        $this->assign('data');
        $this->display('editaction');
    }
    
    // 添加班级成员
     public function postDoupload()
     {
        $group_id=$_POST['group_id'];
        $uid =$_POST['uid'];
        $dmodel=D('member_group');

        $data=$dmodel->add(array(
            'uid'=>$uid,
            'group_id'=>$group_id,  
            'status'=>1,                      
        ));
        $this->success('操作成功',U('Classes/index'));
     }
}