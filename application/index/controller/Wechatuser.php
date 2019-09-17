<?php
namespace app\index\controller;
use mikkle\tp_wechat\Wechat;
use think\Controller;
use think\Db;

class Wechatuser extends Controller {
	public function __construct() {
		parent::__construct();
		header("Content-type:text/html;charset=utf-8");
		if (empty(cache("webconfig"))) {
			cache("webconfig", DB::name("webconfig")->find());
		}
		switch (request()->action()) {
		case 'index':
			$check = 0;
			break;
		case 'search':
			$check = 1;
			break;
		case 'correct_errors':
			$check = 2;
			break;
		default:
			$check = 3;
			break;
		}
		$this->assign('check', $check);
		$this->assign('webconfig', cache("webconfig"));
	}
	//微信用户登录绑定
	public function index() {
		if (input('code')) {
			$data = Wechat::Oauth()->getOauthAccessToken();
			$openid = $data['openid'];
			$access_token = $data['access_token'];
			$userinfo = Wechat::Oauth()->getOauthUserInfo($access_token, $openid);
			$data = ['openid' => $userinfo['openid'], 'wx_user' => $userinfo['nickname'], 'head_img' => $userinfo['headimgurl']];
			$is_user = Db::name("wxuser")->where(['openid' => $userinfo['openid']])->find();
			if (!$is_user) {
				Db::name("wxuser")->insert($data);
				session('user', Db::name("wxuser")->where(['openid' => $userinfo['openid']])->find());
			} else {
              Db::name("wxuser")->where(['openid' => $userinfo['openid']])->update(['wx_user' => $userinfo['nickname'], 'head_img' => $userinfo['headimgurl']]);
				session('user', $is_user);
			}
  
			$this->redirect('binding');
		} else {
			$return = $domain = request()->domain() . url('index');
			$url = Wechat::Oauth()->getOauthRedirect($return, '1');
			header('location:' . $url);
		}
	}
	//微信用户登录绑定
	public function binding() {
		if (request()->isAjax()) {
			$this->check_csrf();
			$user = Db::name("information")->where("usercard", input('usercard'))->find();
			if (!$user) {
				return $this->return_msg('未查找到对应身份证学生信息', 1);
			}
			if ($user['openid'] != NULL || !empty($user['openid'])) {
				return $this->return_msg('信息已被他人验证', 1);
			}
			Db::name("wxuser")->where(['openid' => session('user')['openid']])->update(['user_id' => $user['id']]);
			Db::name("information")->where(['id' => $user['id']])->update(['openid' => session('user')['openid'],'mobile' => input('mobile')]);
			//更新session
			$user = Db::name("information")->where("usercard", input('usercard'))->find();
			session('user', Db::name("wxuser")->where(['openid' => session('user')['openid']])->find());
			return $this->return_msg('信息验证成功');
		}

		if ((int) session('user')['user_id'] > 0) {
             $user_msg = Db::name('information')->where('id=' . session('user')['user_id'] )->find();
          if($user_msg){
          $this->redirect('index/index/index');
          }
		}
		$this->csrf_token();
		$this->webtitle('验证信息');
		return $this->fetch();
	}
     	//缴费回调
   	public function notify(){
		$xml = $GLOBALS ['HTTP_RAW_POST_DATA'];
		$array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);//转换xml数据为数组
       	file_put_contents('text.txt', json_encode($array_data ));

		$update['transaction_id']=$array_data['transaction_id'];//微信支付单号，用户对账
		$order=$array_data['out_trade_no'];//网站订单号
   		$update['status'] = 1;
   		$info = Db::name('orders')->where('oid',$order)->update($update);
   		$msg = Db::name('orders')->where('oid',$order)->value('openid');
      	$picerror=Db::name('picerror')->where('openid',$msg)->find();
      if($picerror['status']==4){
         		$updates_arr['status']=1;
        Db::name('picerror')->where('openid',$msg)->update($updates_arr);
      }
   		
   	}
  
	protected function webtitle($name = "") {
		$title = empty($name) ? cache('webconfig')['title'] : $name;
		$this->assign("title", $title);
	}
	protected function csrf_token() {
		//表单token 验证  防止csrf
		$token_key = config("token_key");
		$microtime = microtime();
		$rand_num = rand(10, 9999999);
		$token = md5(md5($token_key) . md5($microtime) . sha1($rand_num));
		$this->assign("microtime", $microtime);
		$this->assign("rand_num", $rand_num);
		$this->assign('token', $token);
	}
	protected function check_csrf($data = "") {
		$data = empty($data) ? input() : $data;
		//验证表单
		$token_key = config("token_key");
		$token = md5(md5($token_key) . md5($data['microtime']) . sha1($data['rand_num']));
		if ($token != $data["token"]) {
			exit(json_encode($this->return_msg("csrf")));
		} else {
			return true;
		}
	}
	protected function return_msg($key, $code = 0) {
		return array("msg" => $key, "code" => $code);
	}
}
