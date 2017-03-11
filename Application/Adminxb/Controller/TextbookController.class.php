<?php
namespace Adminxb\Controller;

class TextbookController extends AdminController {
    
    public function index() {
        $type = I('type', '', 'trim');
        $map['c.is_delete'] = 0;
        $types[$type] = '全部';
        //获取教材列表
       $list = $this->getWorkList($map);
        $this->assign('list', $list);
       
        $this->display();
    }
    public function getWorkList($map){
        $select=M('xb_textbook ');
        $total = $select->alias('c')
        ->field('c.id,c.name,c.cost,c.sort,c.integral,c.incidentals_name,a.title')
        ->join('dbh_xb_course a ON a.id=c.uid')
        ->where($map)
        ->select();
        return $total;
    }
    // 删除教材
    public function recycle() {
       
        $data['is_delete'] = 1;
        $list = M('xb_textbook');
        if($list->where('id='.$_GET['id'])->save($data)) { 
            $this->success('操作成功');exit;
        }
        $this->error('操作失败');
    }

    //查看
    public function edit(){
       $id = I('get.id', '', 'intval');
       $list = M('xb_textbook')
       ->field('id,name,incidentals_name,cost,integral,sort')
       ->where('id='.$id)
       ->select();

       $this->assign('config',$list);
       $this->display('edit');
    }

  
    //添加教材 为NULL
   public function addAction(){
        $list   =   M("xb_course")
        ->where()
        ->field('id,title')
        ->select();
        $this->assign('config', $list);
       
        $this->display('edit');
    }
    //添加教材 杂费
    public function editaction(){
        $data['uid']=I('post.uid','','intval');
        $data['sort']=I('post.sort','','intval');
        $data['name']=I('post.name','','');
        $data['cost']=I('post.cost','','intval');
        $data['integral']=I('post.integral','','intval');
        $data['time']=time();
        if($_POST['incidentals_name']!==''){
            $data['incidentals_name']=I('post.incidentals_name','','');
        }
        $select=M("xb_textbook")
        ->filter('strip_tags')
        ->add($data);
        if($result !== false){
             $this->success('新增成功！',U('Textbook/index'));
        }else{
             $this->error(D('Work')->getError());
         }
     }
     //添加杂费
     public function addincidentals(){
        $list=M('xb_course')
        ->field('id,title')
        ->select();
        $this->assign('config',$list);
        $this->display('incidentals');
     }
} 
