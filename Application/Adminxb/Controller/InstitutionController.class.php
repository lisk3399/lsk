<?php
/**
 * 机构管理
 */
namespace Adminxb\Controller;
use Adminxb\Model\AuthGroupModel;
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
        $id    =   I('request.ids', '', 'trim');
        $data['is_delete'] =   I('request.is_delete', '', 'intval');
        if(empty($id)){
            $this->error('请选择要操作的数据');
        }
        if(empty($id)){
            $this->error('id为空');
        }
        $Institution = M('xb_orgnization');
        if($Institution->where('id='.$id)->save($data)) { 
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
            $REQUEST = (array)I('request.');
            $page = I('p', '', 'intval');
        //分页配置
            if( isset($REQUEST['r']) ){
                $listRows = (int)$REQUEST['r'];       
            }else{
                $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
            }
            $Classes = M('xb_orgnization');
            $select = $Classes->alias('c')
            ->page($page, $listRows)
             ->field('c.id,c.uid,c.name,c.cover_url,c.is_delete, c.create_time,m.nickname')
            ->join(' DBH_XB_MEMBER m on c.uid = m.uid ', 'left')
            ->where($map)
            ->order('c.create_time desc');
            $list = $select->select();//echo $select->getLastSql();exit;
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

// 修改机构
    public function editAction(){
        $id = I('get.id','','intval');
        empty($id) && $this->error('参数不能为空！');
        $data = M('xb_orgnization')->field(true)->find($id);
        $this->assign('data',$data);
        $this->display('edit');
    }
// 修改机构
    public function saveAction(){
        $res = D('xb_orgnization')->update();
        if(!$res){
            $this->error(D('xb_orgnization')->getError());
        }else{
            $this->success($res['id']?'更新成功！':'新增成功！',U('Institution/index'));
        }
  
    }
    //添加机构

    public function postDoupload(){
        $name=I('post.name');
        $uid=I('post.uid','','intval');
        $time=time();
        $dmodel=D('xb_orgnization');
        $data=$dmodel->add(array(
            'uid'=>$uid,
            'name'=>$name,
            'description'=>$description,
            'create_time'=>$time, 
        ));
        
        $this->success('操作成功',U('Institution/index'));
    }
    public function addInstitution(){
        $this->meta_title = '添加机构';
        $this->assign('data');
        $this->display('editaction');
    }
}