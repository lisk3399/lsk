<?php
namespace Admin\Controller;

use Think\Upload\Driver\Qiniu\QiniuStorage;
use Think\Upload\Driver\Qiniu\Auth;
use Think\Upload\Driver\Qiniu\Etag;
class WorkController extends AdminController {
    
    public function index() {
        $type = I('type', '', 'trim');
        $map['is_delete'] = 0;
        $types[$type] = '全部';
        //获取作品列表
        $list = $this->getWorkList($map);
        $this->assign('list', $list);
        $this->assign('type', $types[$type]);
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

//获取作品列表
    private function getWorkList($map) {
      
        $adminuid= $_SESSION['onethink_admin']['user_auth']['uid'];
        $Admin=M('admin');
        $data=$Admin->where('uid='.$adminuid)->find();
         
        if(!empty($data)){
            
            $type=$data['type'];
            $lian= $data['related_id'];
            //如果为ORG就是机构管理员
            if($type==='ORG'){ 
                $Related=M('content');
                $selectorg=$Related->alias('d')
                ->field('d.id zid,d.org_id,d.title,d.description,d.likes,d.status,d.create_time,c.uid,c.nickname')
                ->join('__MEMBER__ c on c.uid=d.uid')
                ->where('d.status=1 and d.org_id='.$lian)
                ->order('d.create_time desc')
                ->select();
                return $selectorg;
            }
             //如果为GROUP就是班级管理员  
            if($type==='GROUP'){
               $Related=M('content');
               $selectorg=$Related->alias('d')
               ->field('d.id zid,d.group_id,d.title,d.description,d.likes,d.status,d.create_time,c.uid,c.nickname')
               ->join('__MEMBER__ c on c.uid=d.uid')
               ->where('d.status=1 and d.group_id='.$lian)
               ->order('d.create_time desc')
               ->select();
               return $selectorg;
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
        
            $Work = M('content');
            $select = $Work->alias('d')
            ->page($page, $listRows)
            ->field('d.id  zid,d.title,d.description ,d.likes,d.status,d.create_time,c.uid,c.nickname')
            ->join('__MEMBER__ c on c.uid=d.uid')
            ->where('d.status=1')
            ->order('d.create_time  desc')
            ->select();
            $total = $Work->alias('w')->where($map)->count();
            
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
    }

    //查看
    public function edit(){
       $id = I('get.id', '', 'intval');
       $Classes = M('content');
       $list = $Classes->alias('a')
       ->field('a.id,a.uid,a.title,a.description,a.create_time,c.type,c.value,c.cover_url')
       ->join('__CONTENT_MATERIAL__ c on a.id=c.content_id')
       ->where('a.id='.$id)
       ->select();

       $this->assign('list',$list);
       $this->display('Work/edit');
    }
    //修改动态创建时间
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
             $this->success($result['create_time']?'更新成功！':'新增成功！',U('Work/index'));
        }else{
             $this->error(D('Work')->getError());
        }
  }
    // 上传图片视频   
       public function postDoupload()
    {   
            //进度条
        // $name = ini_get('session.upload_progress.name');
        // $key = ini_get("session.upload_progress.prefix") . $_FILES[$name];
        $upload_img=M('content_material');
        $res=D('content');  
        $a=$res->update();
        if(!$res->create()) {
           $this->error($res->geterror());
        }
        if(!empty($_FILES['logo']['tmp_name'][0]) || !empty($_FILES['file']['tmp_name'][0])){
            $filelogo = $_FILES['logo'];
            $namelogo = $_FILES['logo']['name'];
           
            $file = $_FILES['file'];
            $namefile=$_FILES['file']['name'];
            $allow_typefile=array('avi','mp4');
            $typefile = strtolower(substr($namefile,strrpos($namefile,'.')+1));
            $allow_type = array('jpg','jpeg','gif','png'); //定义允许上传的类型
          
           //判断文件类型是否被允许上传
            if(in_array($typefile , $allow_typefile)){}
            elseif($typefile===""){}
            else{
              $this->error('上传格式不对','', array());
            }
            $config = C('QINIU');
            $configdomain=$config['img_domain'];   
            $filename = explode('.', $file['name']);
            //重新生成文件名
            $etag = new Etag();
            $etagname = $etag->GetEtag($file['tmp_name']);
            $ext = $filename[1];
            $fileName = $etagname[0].'.'.$ext;
            $file = array(
                'name'=>'file',
                'fileName'=>$fileName,
                'fileBody'=>file_get_contents($file['tmp_name'])
            ); 
            $upload = new \Think\Upload\Driver\Qiniu\QiniuStorage($config);
            
            foreach ($filelogo['name'] as  $v) { 
            //获取文件后缀
                $filenamelogo=explode('.',$v); 
                $extlogo = $filenamelogo[1];  
                $allow_type = array('jpg','jpeg','gif','png');
                if(in_array($extlogo,$allow_type)){}
                    elseif(is_null($extlogo)){}
                else{$this->error('上传格式不对','', array());}
             //唯一文件名
                $etagnamelogo=md5(uniqid($v));  
                $fileNamelogo=$etagnamelogo.'.'.$extlogo;
                $filelogo=array(
                    'name'=>'logo',
                    'fileName'=>$fileNamelogo,
                    'fileBody'=>file_get_contents($filelogo['tmp_name'])  
                    );
                
                $result[] = $upload->upload(array(),$file, $filelogo);
            }
            if(count($result) > 0){
                //多维数组
                foreach ($result as  $v) {
                   
                    foreach ($v as $v1) {
                        
                  $img_domain = C('QINIU.img_domain');
                  $arr['type'] = 'VIDEO';
                  $arr['value'] = $img_domain.$v1['key'];
                  $arr['cover_url'] = $img_domain.$v1['key'];
                
                  $arrayjson[]=json_encode($arr);
                    }
                  }//转换类型
                   $new2=implode(",",$arrayjson);
                   $dmodel=D('content_material');
                   $data=$dmodel->add(array(
                    'content_id'=>$GLOBALS['id'],
                    'content_json'=>$new2));
                $this->success('上传成功','', $result);
        }else{
            $this->error('上传失败','', array(
                'error'=>$this->qiniu->error,
                'errorStr'=>$this->qiniu->errorStr
            ));
        }
        exit;
      }else{
            $this->success('上传成功','');return;
     }
    }
    //添加动态 为NULL
   public function addAction(){

        $this->meta_title = '新增动态';
        $this->assign('data',null);
        $this->display('editaction');
    }
}