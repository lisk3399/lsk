<?php
/**
 * 校区管理
 */
namespace Adminxb\Controller;
use Adminxb\Model\AuthGroupModel;
use Think\Page;

class CampusController extends AdminController {

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
        $id    =   I('request.ids', '', 'trim');
        $data['is_delete'] =   I('request.is_delete', '', 'intval');
        if(empty($id)){
            $this->error('请选择要操作的数据');
        }
        if(empty($id)){
            $this->error('id为空');
        }
        $Classes = M('xb_campus');
        if($Classes->where('id='.$id)->save($data)) {
            $this->success('操作成功');exit;
        }
        $this->error('操作失败');
    }
//获取校区列表
    public function getClassList($map){
        $REQUEST = (array)I('request.');
        $page = I('p', '', 'intval');
        //分页配置
        if( isset($REQUEST['r']) ){
            $listRows = (int)$REQUEST['r'];
        }else{
            $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
        }

        $Classes = M('xb_campus');
        $select = $Classes->where('is_delete=0')->select();

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
// 编辑校区
    public function editAction(){
        // 接受数据
        $id=I('get.id');
        empty($id) && $this->error('参数不能为空');
        $data=M('xb_campus')->field(true)->find($id);
        $this->assign('data',$data);
        $this->meta_title='编辑校区';
        $this->display('edit');
    }
    //修改校区名称
    public function saveAction(){
        $data['name']=I('post.name','','htmlspecialchars');
        $data['moblie']=I('post.moblie','','intval');
        $data['moblie1'] =I('post.moblie1','','intval');
        $data['address']=I('post.address','','htmlspecialchars');
        $data['cut']=$_POST['cut'];
        $Dao = M("xb_campus");
        $result = $Dao->where('id ='. $_POST['id'])
                     ->save($data);
        if($result !== false){
             $this->success($result['group_name']?'更新成功！':'新增成功！',U('Campus/index'));
        }else{
             $this->error(D('Campus')->getError());
        }
    }
    // 获取校区下成员
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
    //校区搜索
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
    // 添加校区成员
    public function addAction(){
        $this->meta_title = '新增成员';
        $this->assign('data');
        $this->display('editaction');
    }
    
    // 添加校区成员
     public function postDoupload()
     {
        $name=I('post.name','','htmlspecialchars');
        $moblie=I('post.moblie','','intval');
        $moblie1 =I('post.moblie1','','intval');
        $address=I('post.address','','htmlspecialchars');
        $cut=$_POST['cut'];
        $dmodel=D('xb_campus');

        $data=$dmodel->add(array(
            'name'=>$name,
            'moblie'=>$moblie,  
            'moblie1'=>$moblie1,
            'address'=>$address, 
            'cut'=>$cut,                     
        ));
        $this->success('操作成功',U('Campus/index'));
     }
}