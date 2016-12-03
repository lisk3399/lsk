<?php
namespace Admin\Controller;

use Think\Upload\Driver\Qiniu\QiniuStorage;
use Think\Upload\Driver\Qiniu\Auth;
use Think\Upload\Driver\Qiniu\Etag;
class WorkController extends AdminController {
    
    public function index() {
        $type = I('type', '', 'trim');
        if(!empty($type)) {
            $map['w.type'] = $type;
            $map['is_delete'] = 0;
            if(!in_array($type, array('DUBBING', 'LIPSYNC', 'ORIGINAL'))) {
                $this->error('类型不正确');
            }
            $types = array(
                'DUBBING' => '配音秀',
                'LIPSYNC' => '对口型',
                'ORIGINAL' => '原创'
            );
        } else {
            $map['is_delete'] = 0;
            $types[$type] = '全部';
        }
        //获取作品列表
        $list = $this->getWorkList($map);
        
        $this->assign('list', $list);
        $this->assign('type', $types[$type]);
        $this->display();
    }
    
    //设置数据状态
    public function setStatus() {
        $ids    =   I('request.ids', '', 'trim');
        $is_delete =   I('request.is_delete', '', 'intval');
        $uid = I('uid', '', 'intval');
        $material_id = I('material_id', 0, 'intval');
        if(empty($ids)){
            $this->error('请选择要操作的数据');
        }
        if(empty($ids)){
            $this->error('id为空');
        }
        
        $Work = M('work');
        $map['id'] = array('IN', $ids);
        $data['is_delete'] = $is_delete;
        
        if($Work->where($map)->save($data)) {
            //后台删除则对应用户的作品也需要减少
            if($is_delete == 1) {
                WorkApi::setWorkDec($material_id, $uid);
            }//后台恢复对应用户作品增加
            elseif ($is_delete == 0) {
                WorkApi::setWorksInc($material_id, $uid);
            }
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
        
        $Work = M('content');
        $select = $Work->alias('d')
        ->page($page, $listRows)
        ->field('d.id,d.title,d.description ,d.likes,d.create_time,c.uid,c.nickname,w.id,w.material_id,w.type,w.cover_url,w.is_display')
        ->join('__MEMBER__ c on c.uid=d.uid')
        ->join('__WORK__ w on w.id = d.uid', 'left')
        ->where()
        ->order('d.create_time desc');
         $list = $select->select();
        $total = $Work->alias('w')->where($map)->count();
        
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

    //查看
    public function edit(){
       $id = I('get.id');

        $Classes = M('content');
       $list = $Classes->alias('a')
       ->field('a.id,a.uid,a.title,a.description,a.create_time,c.type,c.value,c.cover_url')
       ->join('__CONTENT_MATERIAL__ c on a.id=c.content_id')
       ->where($id.'=a.uid')
       ->select();

       $this->assign('list',$list);
       $this->display('Work/edit');
    }
    // 上传图片视频
    public function postDoupload()
    {
        $upload_img=M('content_material');
        $res=D('content');  

            $a=$res->update();
           
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
        $allow_typefile=array('avi');

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
        $result[] = $upload->upload(array(), $file,$filelogo);

        if($result){      
            $array[]='http://'.$configdomain.'/'.$fileNamelogo;
            $array[]='http://'.$configdomain.'/'.$fileName;
            $arrayjson=json_encode($array);
            $dmodel=D('content_material');

            $data=$dmodel->add([
                            'content_id'=>$GLOBALS['id'],
                            'content_json'=>$arrayjson,                        
        ]);
        
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
}