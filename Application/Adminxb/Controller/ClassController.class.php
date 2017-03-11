<?php
namespace Adminxb\Controller;
use Adminxb\Model\AuthGroupModel;
use Think\Page;
class ClassController extends AdminController {
     
    public function index(){
       $map['is_delete']=0;
       $list=$this->getClassList($arr);
       $this->assign('list',$list);
       $this->display();
    }
    //首页显示
    public function getClassList($map)
    {
        $REQUEST = (array)I('request.');
        $page = I('p', '', 'intval');
        if( isset($REQUEST['r']) ){
            $listRows = (int)$REQUEST['r'];
        }else{
            $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
        }
        $time=time();
        $select=M('xb_classes  ')
        ->where('is_delete=0')
        ->select();
        foreach ($select as $key => $v) {
            if($v['terminal_time']>=$time){
                $arr[]=$v;
            }else{
                $data['an']=1;
                $select=M('xb_classes')
                 ->where('id='.$v['id'])
                 ->save($data);
                 $arr[]=$v;
            }
        }
        return $arr;
    }	
    //新建班级
    public function addAction(){
    	$this->meta_title='';
    	$this->assign('data');
    	$this->display('editaction');
    }
    //新建班级
    public function postDoupload()
    {
    	$User = D("Orgnization");
 		if (!$User->create()){
            exit($User->getError());
		}else{
	     $dmodel=M('xb_classes');
	     $data=$dmodel->add(array(
	     	'uid'=>$_POST['uid'],
	     	'group_name'=>$_POST['group_name'],
	     	'course'=>$_POST['course'],
	     	'charge'=>$_POST['charge'],
	     	'schooling'=>$_POST['schooling'],
	     	'start_time'=>time(),
	     	'create_time'=>strtotime($_POST['create_time']),
	     	'terminal_time'=>strtotime($_POST['terminal_time']),
	     	'teacher'=>$_POST['teacher'],
	     	'number_passenger'=>$_POST['number_passenger'],
	     	'description'=>$_POST['description'],
	     	));
	     $this->success('添加成功',U('Class/index'));
		}
    }
    //查看班级
    Public function edit(){
    	$id=I('get.id','','intval');
        empty($id) && $this->error();
        $data=M('xb_classes')->field(true)->find($id);
        $this->assign('data',$data);
        $this->meta_title='';
        $this->display('edit');
    }
    //保存班级
    public function compile(){
         $data['group_name']=I('post.group_name','','htmlspecialchars');
         $data['schooling']=I('post.schooling','','intval');
        $Dao = M("xb_classes");
        $result = $Dao->where('id ='. $_POST['id'])
                     ->save($_POST);
        if($result !== false){
             $this->success($result['xb_classes']?'更新失败':'更新成功',U('Class/index'));
        }else{
             $this->error(D('Campus')->getError());
        }
    }
    //删除班级
    public function setStatus()
    {
        $data['is_delete'] =   I('request.is_delete', '', 'intval');
        $select = M('xb_classes');
        if($select->where('id='.$_GET['id'])->save($data)) { 
            $this->success('删除成功');exit;
        }
        $this->error();
    }
    //按时间搜索
    public function search(){
     
        $create_time=strtotime($_GET['create_time']);
        $terminal_time=strtotime($_GET['terminal_time']);
       $select=M('xb_classes')
       ->where("create_time>=$create_time and terminal_time<=$terminal_time")
       ->select();
       $this->assign('list',$select);
       $this->display('index');
    }
    //班级成员直接录入
    public function editAction(){
        $GLOBALS['uid'] = I('get.uid');
        $this->meta_title = '';
        $this->assign('data');
        $this->display('member');
    }
    //成员续费
    public function addclass(){
        $data['name']=I('post.name','','htmlspecialchars');
        $data['tuition']=I('post.tuition','','intval');
        $data['textbook']=I('post.textbook','','intval');
        $data['incidentals']=I('post.incidentals','','intval');
        if(!empty($_POST['age'])){
            $data['uid']=$_POST['uid'];
            $data['gender']=$_POST['gender'];
            $data['age'] =I('post.age');
            $data['Rphone']=I('post.Rphone','','intval');
            $data['state']=$_POST['state'];
                $dmodel=M('xb_class_member');
                $data=$dmodel->add($data);
         $this->success('????ɹ?',U('Class/index'));
        }
        $select=M('xb_class_member');
        if($select->where('id='.$_POST['id'])->save($data)) { 
            $this->success('????ɹ?');exit;
        }
        $this->error();
    }
    //班级成员
    public function member(){
        $GLOBALS['uid']=$_GET['id'];
       $select=M('xb_class_member')
       ->where('uid='.$GLOBALS['uid'])
       // ->where("is_select=0")
       ->select();
       $this->assign('list',$select);
       $this->display('classe');
    }
    //删除成员
    public function Delete(){
        $data['is_select'] =   I('get.is_select', '', 'intval');
        $select = M('xb_class_member');
        if($select->where('id='.$_GET['id'])->save($data)) { 
            $this->success('删除成功');exit;
        }
        $this->error();
    }
    //成员续费
    public function cost(){
        $data['id']=$_GET['id'];
        $select=M('xb_class_member')
        ->where('id='.$data['id'])
        ->select();
        $this->assign('list',$select);
        $this->display('cost');
    }
    //考勤记录录入
    public function clockingin(){
        $id=$_GET['id'];
        $select=M('xb_class_member')
        ->where("uid=".$id)
        ->where("is_select=0")
        ->select();
        $this->assign('list',$select);
        $this->display('clockingin');
    }
    //考勤记录
    public function clockinginadd(){
      $data['name']=$_POST['name'];
      $data['discipline']=$_POST['discipline'];
      $data['active']=$_POST['active'];
      $data['group_name']=$_POST['group_name'];
      $data['is_state']=$_POST['is_state1'];
      foreach ($data as $key => $v) {
          var_dump($key);exit;
      }
    }
}