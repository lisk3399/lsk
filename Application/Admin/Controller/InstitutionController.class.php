<?php
/**
 * 机构管理
 */

    
namespace Admin\Controller;
use Admin\Model\AuthGroupModel;
use Think\Page;

class InstitutionController extends AdminController {


       public function index()
    {
        if ($_GET['nickname']) 
        {
        $name = $_GET['nickname'];   
        $where['name'] =['like','%'.$name.'%'];
      $list = M('_orgnization  as  c')
      ->join('__MEMBER__  as  m  on  c.uid = m.uid')
      ->where($where)->select();
            
       $this->assign('list', $list);

       $this->display();
        }

    else{ 
             if(!empty($type)) 
        {
            $map['w.type'] = $type;
            $map['is_delete'] = 0;
            if(!in_array($type, array('DUBBING', 'LIPSYNC', 'ORIGINAL')))
             {
                $this->error('类型不正确');
            }
            $types = array(
                'DUBBING' => '机构',
               
            );
        } 
        else
         {
            $map['is_delete'] = 0;
            $types[$type] = '全部';
        }

        //获取机构列表
        $list = $this->getWorkList($map); 

         int_to_string($list);
        $this->assign('list', $list);
        $this->assign('type', $types[$type]);  
        $this->display();
    }
}
  
/**
     * 显示左边菜单，进行权限控制
     * @author huajie <banhuajie@163.com>
     */

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

 public function mydocument($status = null, $title = null){
        //获取左边菜单
        $this->getMenu();

        $Document   =   D('Document');
        /* 查询条件初始化 */
        $map['uid'] = UID;
        if(isset($title)){
            $map['title']   =   array('like', '%'.$title.'%');
        }
        if(isset($status)){
            $map['status']  =   $status;
        }else{
            $map['status']  =   array('in', '0,1,2');
        }
        if ( isset($_GET['time-start']) ) {
            $map['update_time'][] = array('egt',strtotime(I('time-start')));
        }
        if ( isset($_GET['time-end']) ) {
            $map['update_time'][] = array('elt',24*60*60 + strtotime(I('time-end')));
        }
        //只查询pid为0的
        $map['pid'] = 0;
        $list = $this->lists($Document,$map,'update_time desc');
        $list = $this->parseDocumentList($list,1);

        // 记录当前列表页的cookie
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->assign('status', $status);
        $this->assign('list', $list);
        $this->meta_title = '我的机构';
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

        $is_delete =   I('request.is_delete', '', 'intval');
        
        
        if(empty($ids)){
            $this->error('请选择要操作的数据');
        }
        if(empty($ids)){
            $this->error('id为空');
        }
        $Institution = M('orgnization');

        $map['id'] = array('IN', $ids);

        $data['is_delete'] = $is_delete;

        if($Institution->where($map)->save($data)) { 

            $this->success('操作成功');
            exit;
            // $this->success('操作成功');  exit;
        }
        $this->error('操作失败');
    }
    
    /*
     * 获取机构列表
     */
          
    public function getWorkList($map) {

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
    // 修改机构
       public function editAction(){
        $id = I('get.id');

        empty($id) && $this->error('参数不能为空！');
        $data = M('orgnization')->field(true)->find($id);

        $this->assign('data',$data);
        $this->meta_title = '编辑机构';
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

   
  

}