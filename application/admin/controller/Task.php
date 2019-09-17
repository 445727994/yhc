<?php
/*
 * @auther 萤火虫
 * @email  445727994@qq.com
 * @create_time 2019-04-24 01:22:38
 */
namespace app\admin\controller;
use app\admin\model\Information;
use think\Controller;
use think\Db;

class Task extends Base {
	public function index() {
		$page = input("page");
		if (empty($page)) {
			$page = 1;
		}
		$pagesize = 100;
		$query = "select * from information limit " . (($page - 1) * $pagesize) . "," . $pagesize;
		$inf = Db::connect(config()['app']['d2'])->query($query);
		$information = new Information();
		if ($inf) {
			foreach ($inf as $key => $value) {
				$res = $information->allowField(true)->isUpdate(false)->save($value, false);
				if (!$res) {
					file_put_contents('./' . date('Ymd') . 'all.txt', $value['id'] . "\r\n", FILE_APPEND);
				}
			}
			$this->success('新增成功', url("task/index", ['page' => $page + 1]));
		}

	}
  	public function auto(){
   		 $file=scandir('./schoolimg/auto/'); 
      	if(count($file)>1002){
        	$this->error('图片数量大于1000,请减少图片', url("task/auto", ['page' => $page + 1]));
        }
      $i=0;
      foreach($file as $k=>$v){
      	$usercard= trim($v,'.jpg');
      
        if(!empty($usercard)){
        	$user=DB::name("information")->field('schoolcode,usercard,times,cj_time')->where('usercard',$usercard)->find();
          if($user){
          	$new_src = "./schoolimg/{$user['cj_time']}/{$user['times']}/{$user['schoolcode']}/{$user['usercard']}.jpg";
          	$dir = "./schoolimg/{$user['cj_time']}/{$user['times']}/{$user['schoolcode']}";
			Directory($dir);
			rename(iconv("gb2312", "utf-8//IGNORE", './schoolimg/auto/'.$usercard.".jpg"), iconv("gb2312", "utf-8//IGNORE", $new_src));
          	$i++;
          }else{
          	echo "身份证号码".$usercard .'未找到';
          }
        }
      }
     echo "自动识别完成！共识别".$i."张图像";
      exit;
    }
	public function finduser() {
		$page = input("page");
		if (empty($page)) {
			$page = 1;
		}

		$user = Db::connect(config()['app']['d2'])->name("ordered")->field('openid,dotime as create_time,phone as mobile ,uptime as appoint_time,remark,status,need_send,error_type,need_send,addr')->order('id asc')->limit(($page - 1) * 500, 500)->select();
		if ($user) {
			foreach ($user as $key => $value) {
				$value['appoint_time'] = $value['appoint_time'] == null ? 0 : $value['appoint_time'];
				$value['type'] = 2;
				$value['update_time'] = $value['create_time'];
				var_dump($value['create_time']);exit;
				//$res = DB::name('picerror')->insert($value);

			}

			$this->success('新增成功', url("task/finduser", ['page' => $page + 1]));
		}
	}
	public function removepic() {
			$page = input("page");
		if (empty($page)) {
			$page = 1;
		}
		$informations = DB::name("information")->field('schoolcode,usercard,times,cj_time')->order('id asc')->paginate(5000);

      if(!$informations){
      	exit('suc');
      }
		foreach ($informations as $key => $user) {
			$old_src = "./static/images/{$user['schoolcode']}/{$user['usercard']}.jpg";
			if (file_exists($old_src)) {
              var_dump($old_src);
				$new_src = "./schoolimg/{$user['cj_time']}/{$user['times']}/{$user['schoolcode']}/{$user['usercard']}.jpg";
				$dir = "./schoolimg/{$user['cj_time']}/{$user['times']}/{$user['schoolcode']}";
				Directory($dir);
				rename(iconv("gb2312", "utf-8//IGNORE", $old_src), iconv("gb2312", "utf-8//IGNORE", $new_src));
			} else {
			//	file_put_contents('./log/' . $user['schoolcode'], $user['usercard'] . PHP_EOL, FILE_APPEND);
			}

		}
		$this->success('更新成功', url("task/removepic", ['page' => $page + 1]));

	}
  public function sex() {
		$page = input("page");
		if (empty($page)) {
			$page = 1;
		}
		$informations =Db::connect(config()['app']['d2'])->name("information")->field('schoolcode,usercard,sex')->order('id asc')->paginate(5000);

      if(!$informations){
      	exit('suc');
      }
		foreach ($informations as $key => $user) {
			if($user['sex']=="男"){
             DB::name("information")->where(['usercard'=>$user['usercard']])->update(['sex'=>1]);
            }

		}
		$this->success('更新成功', url("task/sex", ['page' => $page + 1]));

	}
  	public function removepic2() {
			$page = input("page");
		if (empty($page)) {
			$page = 1;
		}
		$informations = DB::name("information")->field('schoolcode,usercard,times,cj_time')->where('schoolcode=10479 ')->order('id asc')->paginate(5000);

      if(!$informations){
      	exit('suc');
      }
		foreach ($informations as $key => $user) {
			$old_src = "./static/images/{$user['schoolcode']}/{$user['usercard']}.jpg";
          
			if (file_exists($old_src)) {
              var_dump($old_src);
				$new_src = "./schoolimg/{$user['cj_time']}/{$user['times']}/{$user['schoolcode']}/{$user['usercard']}.jpg";
				$dir = "./schoolimg/{$user['cj_time']}/{$user['times']}/{$user['schoolcode']}";
				Directory($dir);
				rename(iconv("gb2312", "utf-8//IGNORE", $old_src), iconv("gb2312", "utf-8//IGNORE", $new_src));
			} else {
			//	file_put_contents('./log/' . $user['schoolcode'], $user['usercard'] . PHP_EOL, FILE_APPEND);
			}

		}
      exit;
		$this->success('更新成功', url("task/removepic", ['page' => $page + 1]));


	}
  public function removeorder() {
			$page = input("page");
		if (empty($page)) {
			$page = 1;
		}
		$informations = Db::connect(config()['app']['d2'])->name("orders")->order('id asc')->paginate(5000);

      if(!$informations){
      	exit('suc');
      }
		foreach ($informations as $key => $user) {
          if(!DB::name('orders')->where(['oid'=>$user['oid']])->find()){
          DB::name('orders')->insert($user);
          }
          	

		}
		$this->success('更新成功', url("task/removeorder", ['page' => $page + 1]));

	}
    public function removeorders() {
			$page = input("page");
		if (empty($page)) {
			$page = 1;
		}
		$informations = Db::connect(config()['app']['d2'])->name("orders")->order('id asc')->paginate(5000);

      if(!$informations){
      	exit('suc');
      }
		foreach ($informations as $key => $user) {
          if(!DB::name('picerror')->where(['oid'=>$user['oid']])->find()){
          DB::name('orders')->insert($user);
          }
          	

		}
		$this->success('更新成功', url("task/removeorder", ['page' => $page + 1]));

	}

}
