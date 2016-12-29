<?php
namespace Admin\Controller;

use Think\Upload\Driver\Qiniu\QiniuStorage;
use Think\Upload\Driver\Qiniu\Auth;
use Think\Upload\Driver\Qiniu\Etag;
class ProductionController extends AdminController {
    
    public function index() {
        $map['task_id'] = array('gt', 0);
        //获取作品列表
        $list = $this->getWorkList($map);
        
        $this->assign('list', $list);
        $this->display();
    }
    
    //设置数据状态
    public function setStatus() {
      
        $ids    =   I('request.ids', '', 'trim');
        $status =   I('request.status', '', 'intval');
        if(empty($ids)){
            $this->error('请选择要操作的数据');
        }
        if(empty($ids)){
            $this->error('id为空');
        }
        $Institution = M('content');
        $map['id'] = array('IN', $ids);
        $data['status'] = $status;
        if($Institution->where($map)->save($data)) { 

            $this->success('操作成功');
            exit;
            // $this->success('操作成功');  exit;
        }
        $this->error('操作失败');
    }
 
    
    //设置是否在首页显示
    public function setDisplay() {
        $ids    =   I('request.ids', '', 'trim');
        $is_display =   I('request.is_display', '', 'intval');
        if(empty($ids)){
            $this->error('请选择要操作的数据');
        }
        if(empty($ids)){
            $this->error('id为空');
        }
    
        $Work = M('work');
        $map['id'] = array('IN', $ids);
        $data['is_display'] = $is_display;
    
        if($Work->where($map)->save($data)) {
            $this->success('操作成功');
            exit;
        }
        $this->error('操作失败');
    }
    
    public function recycle() {
        $map['is_delete'] = 1;
        $list = $this->getWorkList($map);
        
        $this->assign('list', $list);
        $this->display();
    }
    
    /*
     * 获取作品列表
     */
    private function getWorkList($map) {
        $REQUEST = (array)I('request.');
        $page = I('p', '', 'intval');
        //分页配置
        if( isset($REQUEST['r']) ){
            $listRows = (int)$REQUEST['r'];
        }else{
            $listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
        }
        
        $map['d.status'] = 1;
        $Work = M('content');
        $select = $Work->alias('d')
        ->page($page, $listRows)
        ->field('d.id as zid,d.title,d.description,d.likes,d.status,d.is_admin,d.create_time,c.uid,c.nickname,t.id as task_id')
        ->join('__MEMBER__ c on c.uid=d.uid', 'left')
        ->join('__TASK__ t on t.id = d.task_id', 'left')
        //->join('__WORK__ w on w.id = d.uid')
        ->where($map)
        ->order('d.id desc')
        ->select();
        $total = $Work->alias('d')->where($map)->count();
     
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

    //查看
    public function edit(){
       $id = I('get.id');

        $Classes = M('content');
       $list = $Classes->alias('a')
       ->field('a.id,a.uid,a.title,a.description,a.create_time,c.type,c.value,c.cover_url')
       ->join('__CONTENT_MATERIAL__ c on a.id=c.content_id')
       ->where('a.id='.$id)
       ->select();

       $this->assign('list',$list);
       $this->display('Production/edit');
    }
    // 上传图片视频   
    public function postDoupload()
    {
        $upload_img=M('content_material');

        $create_time = strtotime ($_POST['create_time']);
        $deadline=strtotime( $_POST['deadline']);
        $tag_id = I('tag_id', '', 'intval'); //作业标签id
        if(empty($tag_id)) {
            $this->error('标签id不能为空');
        }
        if(empty($create_time) || empty($deadline)) {
            $this->error('时间不能为空');
        }
        
        $is_admin = I('is_admin', '', 'intval');
        if($is_admin) {
            $taskModel = M('task');
            $data['create_time'] = $create_time; 
            $data['deadline'] = $deadline;
            $data['tag_id'] = $tag_id;
            $data['is_admin']=$_POST['is_admin'];
            $task_id = $taskModel->add($data);
            $_POST['task_id'] = $task_id;
        }
        unset($_POST['tag_id']);
        
        $res=D('content');  
            $a=$res->update1();

                    if($res->create())
                    {
                        $this->success('添加成功');
                    }
                    else
                    {
                        $this->error($res -> geterror());
                    } 
        $filelogo = $_FILES['logo'];
        $namelogo = $_FILES['logo']['name'];
       
        $file = $_FILES['file'];
        $namefile=$_FILES['file']['name'];
        $type = strtolower(substr($namelogo,strrpos($namelogo,'.')+1));
        $typefile = strtolower(substr($namefile,strrpos($namefile,'.')+1));

        $allow_type = array('jpg','jpeg','gif','png'); //定义允许上传的类型
        $allow_typefile=array('avi','mp4');

        //判断文件类型是否被允许上传
        if(in_array($type , $allow_type)&& !in_array($typefile, $allow_typefile)){
           
        }
        else if(!in_array($type , $allow_type) && in_array($typefile,$allow_typefile)){
            
        }
        else if(in_array($type,$allow_type) && in_array($typefile,$allow_typefile)){
           
        }
        else{
          $this->error('上传格式不对','', array());
        }

        $config = C('QINIU');
        $configdomain=$config['domain'];    
        $filename = explode('.', $file['name']);
        $filenamelogo = explode('.', $filelogo['name']);

        //重新生成文件名
        $etag = new Etag();
        $etagname = $etag->GetEtag($file['tmp_name']);
        $etagnamelogo = $etag->GetEtag($filelogo['tmp_name']);
        $ext = $filename[1];
        $fileName = $etagname[0].'.'.$ext;
        $extlogo = $filenamelogo[1];
        $fileNamelogo = $etagnamelogo[0].'.'.$extlogo;

        
        $file = array(
            'name'=>'file',
            'fileName'=>$fileName,
            'fileBody'=>file_get_contents($file['tmp_name'])
        ); 
        $filelogo = array(
            'name'=>'logo',
            'fileName'=>$fileNamelogo,
            'fileBody'=>file_get_contents($filelogo['tmp_name'])
        );        

        $upload = new \Think\Upload\Driver\Qiniu\QiniuStorage($config);

        $result = $upload->upload(array(), $file,$filelogo);
        //todo: 图片视频分别判断是否为空，不为空的走upload,upload方法不要一下操作图片和视频上传，保证上传方法功能单一性;upload被改的没法重用了
        if(count($result) > 0){
                $img_domain = C('QINIU.img_domain');
                $arr[0]['type'] = 'VIDEO';
                $arr[0]['value'] = $img_domain.$result[0]['key'];
                $arr[0]['cover_url'] = $img_domain.$result[1]['key'];
                $arrayjson=json_encode($arr);   
                $dmodel=D('content_material');
                $data=$dmodel->add(array(
                    'content_id'=>$GLOBALS['id'],
                    'content_json'=>$arrayjson,
                ));
            $this->success('上传成功','', $result);
        }else{
            $this->error('上传失败','', array(
                'error'=>$this->qiniu->error,
                'errorStr'=>$this->qiniu->errorStr
            ));
        }
        exit;
    }
    //添加动态 为NULL
   public function addAction(){

        $this->meta_title = '新增动态';
        $this->assign('data',null);
        $this->display('editaction');
    }
   Public function saveAction()
    {
        $data['title']=$_POST['title'];
        $data['description']=$_POST['description'];
        $data['cover_url']=$_POST['cover_url'];
        $data['create_time'] =strtotime($_POST['create_time']);
        $Dao = M("content");
        $result = $Dao->where('id ='. $_POST['cate_id'])
                      ->save($data);
        if($result !== false){
             $this->success($result['create_time']?'更新成功！':'新增成功！',U('Production/index'));
        }else{
             $this->error(D('Production')->getError());
        }
  }
}