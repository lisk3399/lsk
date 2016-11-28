<?php
namespace Admin\Model;
use Think\Model;
/**
* 修改评论内容
*/
class CommentModel extends Model
{
	
	public function update()
	{
	
		// 获取数据对象
		$data= $this->create($_POST);
		
		if(empty($data))
		{
			
			return false;
		}
		 $status = $this->save();//更新内容

		if(fave===$status)
			{
				$this->error='更新成功！';
				return false;
			}
		
		// 删除缓存
		S('action_list',null );
		// 更新，添加成功
		
		return $data;
	}
}