<?php
/**
 * 机构管理
 */
namespace Admin\Controller;
use Admin\Model\AuthGroupModel;
use Think\Page;

class InstitutionController extends AdminController {
    public function index(){
        if (I('get.nickname','','htmlspecialchars')) 
        {
            $name = I('get.nickname','','htmlspecialchars');
            $where['name'] =array('like','%'.$name.'%');
            $list = M('_orgnization  as  c')
            ->join('__MEMBER__  as  m  on  c.uid = m.uid')
            ->where($where)
            ->select();   
            $this->assign('list', $list);
            $this->display();
        }else{ 
            $map['is_delete'] = 0;
            $types[$type] = '全部';
            //获取机构列表
            $list = $this->getWorkList($map); 
            int_to_string($list);
            $this->assign('list', $list);
            $this->assign('type', $types[$type]);  
            $this->display();
        }
    }
  
//显示左边菜单，进行权限控制
    public function action(){
            //获取列表数据
            $Action =   M('Action')->where(array('status'=>array('gt',-1)));
            $list   =   $this->lists($Action);
            int_to_string($list);
            // 记录当前列表页的cookie
            Cookie('__forward__',$_SERVER['REQUEST_URI']);
            $this->assign('_list', $list);
            $this->meta_title = '机构';
            $this->display();
    }
    public function recycle() {
        $map['is_delete'] = 1;
        $list = $this->getWorkList($map);
        $this->assign('list', $list);
        $this->display();
    }
  
// 删除功能
    public function setStatus() {
        $ids    =   I('request.ids', '', 'trim');
        $data['is_delete'] =   I('request.is_delete', '', 'intval');
        if(empty($ids)){
            $this->error('请选择要操作的数据');
        }
        if(empty($ids)){
            $this->error('id为空');
        }
        $Institution = M('orgnization');
        $map['id'] = array('IN', $ids);
        if($Institution->where($map)->save($data)) { 
            $this->success('操作成功');exit;
        }
        $this->error('操作失败');
    }

// 查看机构下属的班级
    public function classe(){
        $id = I('get.id','','intval');
        $Classes = M('group as c');
        $list = $Classes
        ->field('c.id,c.uid,c.org_id,c.group_name,a.id,a.name')
        ->join('__ORGNIZATION__  a ON  c.org_id = a.id')
        ->where($id.'=c.org_id')
        ->select();
       $this->assign('list',$list);
       $this->display('classe');
    }

//获取机构列表   
    public function getWorkList($map){
        $adminuid= $_SESSION['onethink_admin']['user_auth']['uid'];
        $Admin=M('admin');
        $data=$Admin->where('uid='.$adminuid)->find();
        
        if(!empty($data)){
            
            $type=$data['type'];
            $lian= $data['related_id'];
            //如果为ORG就是机构管理员
            if($type==='ORG'){
                
                $Classes=M('orgnization');
                 $select = $Classes->alias('c')
                 ->page($page, $listRows)
                 ->field('c.id,c.uid,c.name,c.cover_url,c.is_delete, c.create_time,m.nickname')
                 ->join(' __MEMBER__ m on c.uid = m.uid ', 'left')
                 ->where('c.is_delete=0 and c.id='.$lian)
                 ->order('c.uid desc')
                 ->select();
                return $select;
            }
        }else{
            $REQUEST = (array)I('request.');
            $page = I('p', '', 'intval');
        //分页配置
            if( isset($REQUEST['r']) ){
                $listRows = (int)$REQUEST['r'];       
            }else{
                $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
            }
            $Classes = M('orgnization');
            $select = $Classes->alias('c')
            ->page($page, $listRows)
             ->field('c.id,c.uid,c.name,c.cover_url,c.is_delete, c.create_time,m.nickname')
            ->join(' __MEMBER__ m on c.uid = m.uid ', 'left')
            ->where($map)
            ->order('c.uid desc');
            $list = $select->select();
            $total = $Classes->alias('c')->where($map)->count();
            $page = new \Think\Page($total, $listRows, $REQUEST);
            if($total>$listRows){
                $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
            }
            $p =$page->show();
            // 搜索分页
            $this->assign('_page', $p? $p: '');
            $this->assign('_total',$total);
            $options['limit'] = $page->firstRow.','.$page->listRows;
     
            return $list;
        }
       
    }

// 修改机构
    public function editAction(){
        $id = I('get.id','','intval');
        empty($id) && $this->error('参数不能为空！');
        $data = M('orgnization')->field(true)->find($id);
        $this->assign('data',$data);
        $this->display('edit');
    }
// 修改机构
    public function saveAction(){
        $res = D('orgnization')->update();
        if(!$res){
            $this->error(D('orgnization')->getError());
        }else{
            $this->success($res['id']?'更新成功！':'新增成功！',U('Institution/index'));
        }
  
    }
    //添加ORG
    public function addition(){
        $GLOBALS['Institution'] =I('get.id','','htmlspecialchars');
        $this->meta_title = '添加管理';
        $this->assign('data');
        $this->display('editaction');
    }
    public function postDoupload(){
        $related_id=I('post.related_id','','intval');
        $uid=I('post.uid','','intval');
        $time=time();
        $dmodel=D('admin');
        $data=$dmodel->add(array(
            'uid'=>$uid,
            'type'=>'ORG',
            'related_id'=>$related_id,  
            'create_time'=>$time, 
        ));
        
        $this->success('操作成功',U('Institution/index'));
    }
}