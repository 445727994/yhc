<?php
namespace app\index\controller;
use app\admin\model\Change;
use app\admin\model\Picerror;
use app\index\model\Wxpay;
use think\Db;

class Index extends Base {
	public function status() {
		$oid = input('oid');
		$status = Db::name("orders")->where(['oid' => $oid])->value('status');
		return $status;
	}
	public function pay() {
		$session_user = session('user');
		if (request()->isAjax()) {
			Db::name('orders')->where(['openid' => $session_user['openid']])->delete();
			Db::name("picerror")->where(['openid' => $session_user['openid']])->delete();
			return $this->return_msg("取消成功");
		}
		$wxpay = new Wxpay();
		$orders = Db::name('orders')->where(['openid' => $session_user['openid']])->find();
		$ps_num = Db::name('information')->where(['openid' => $session_user['openid']])->value('ps_number');

		if ($orders['status'] == 1 || !empty($ps_num)) {
			$this->webtitle('缴费信息');
			return $this->return_msg('缴费成功', 'suc', url('index'), '去首页');
			exit;
		}
		$user = Db::name("information")->where(['id' => $session_user['user_id']])->field('view_send,schoolcode')->find();
		$post_money = 0;
		if ($user && $user['view_send'] == 1) {
			$post_money = Db::name('school')->where(['code' => $user['schoolcode']])->value('money');
			if (!$post_money) {
				$post_money = Db::name('webconfig')->where('id=1')->value('money1');
			}
		}
		$picerror = Db::name("picerror")->where(['openid' => $session_user['openid']])->value('need_send');
		$ps_money = Db::name('school')->where(['code' => $user['schoolcode']])->value('pay_money');
		if (!$ps_money) {
			$ps_money = Db::name('webconfig')->where('id=1')->value('money');
		}
		$order_money = (int)$ps_money + (int)$post_money;
        if (!$orders) {
          if(!$picerror){
          	$this->redirect('index');
          }
			$orders = [
				'openid' => $session_user['openid'],
				'user_id' => $session_user['user_id'],
				'oid' => date("YmdHis") . mt_rand(10000, 99999),
				'status' => 0,
				'money' => $order_money,
				'need_send' => $picerror,
				'uptime' => time(),
              		'create_time' => time(),
			];
			Db::name("orders")->insert($orders);
		}
		if ($orders['money'] != $order_money || ($orders['uptime'] + 15 * 60) < time()) {
			$ordered = [
				'oid' => date("YmdHis") . mt_rand(10000, 99999),
				'status' => 0,
              'user_id' => $session_user['user_id'],
				'money' => $order_money,
				'uptime' => time(),
			];
			Db::name("orders")->where(['openid' => $session_user['openid'], 'status' => 0])->update($ordered);
		}
		
		$data = $wxpay->payByOrderNo($orders['oid']);
		$this->assign('amount', $data['amount']);
		$this->assign('order_no', $orders['oid']);
		$this->assign("jsApiParameters", $data['jsApiParameters']);
		$this->assign('openid', $session_user['openid']);
		$this->assign('title', '微信安全支付');
		$this->assign('msg', '请支付' . $data['amount'] . "元（拍照{$ps_money},邮寄{$post_money}）<br>订单号:" . $orders['oid']);
		return $this->fetch('pay');
	}
	public function index() {
		$this->webtitle('信息查询');
		$userinfo = Db::name("information")->where(['id' => $this->user['user_id']])->find();
		$exchange = Db::name("change")->where('card1|card2', $userinfo['usercard'])->where('status=0 or status=2')->find();
		if ($exchange) {
			$this->redirect('index/search');
		}
$error = Db::name('picerror')->where('openid', $this->user['openid'])->find();
		$userinfo['userimg'] = get_img($userinfo);
		$this->assign("user", $userinfo);
	$this->assign("error", $error);
		return $this->fetch();
	}
	public function change_confirm() {
		$id = input("id");
		$res = Db::name('change')->where(['id' => $id, 'card2' => input('card')])->update(['status' => (int) input('status')]);
		if ($res) {
			return $this->return_msg('操作成功', 'suc');
		}
		return $this->return_msg('操作失败', 'err');
	}
	public function reupload() {
		$this->webtitle('图像重新上传');
		$id = input("id");
		$error = Db::name('picerror')->where('openid', $this->user['openid'])->find();
		if ($error) {
			if ($error['status'] != '9') {
				$this->redirect('index/search');
			}
		}
		$userinfo = Db::name("information")->where(['id' => $this->user['user_id']])->value('is_confirm');
		if ($userinfo == 1) {
			return $this->return_msg('图像已确认', 'suc');
		}
		$exchange = Db::name("change")->where('card1|card2', $userinfo['usercard'])->where('status=0 or status=2')->find();
		if ($exchange) {
			$this->redirect('index/search');
		}
		if ($error) {
			if ($error['status'] != '9') {
				$this->redirect('index/search');
			}
		}

		if (request()->isAjax()) {
			$pic = Db::name('picerror')->where('openid', $this->user['openid'])->find();
			if ($pic['status'] != 9) {
				return $this->return_msg('暂不需要自行上传', 1);
			}
			if (file_exists('.' . $pic['picsrc'])) {
				unlink('.' . $pic['picsrc']);
			}
			$data['picsrc'] = input("img");
			$data['id'] = $pic['id'];
			$data['status'] = 1;

			$Pic = new Picerror();
			$Pic->_save($data);
			return $this->return_msg('重新上传成功,请耐心等待管理审核');
		}
		return $this->fetch();
	}
	public function search() {
		//预约、补拍信息
		$this->webtitle('图像信息');
		$pic = Db::name("picerror")->where('openid', $this->user['openid'])->find();
      	if($pic&&$pic['status']==4){
        	$this->redirect('pay');
        }
		//图像交换信息
		$userinfo = Db::name("information")->where(['id' => $this->user['user_id']])->field('view_subscribe,view_voluntarily,usercard,is_confirm')->find();
		if ($userinfo['is_confirm'] == 1) {
			return $this->return_msg('图像已确认', 'suc');
		}
		$change = Db::name("change")->where('card1|card2', $userinfo['usercard'])->all();
		$msg = '暂无';
		if ($userinfo['view_subscribe'] == 1) {
			$msg .= '预约';
		}
		if ($userinfo['view_voluntarily'] == 1) {
			$msg .= $userinfo['view_subscribe'] == 1 ? ',补拍' : '补拍';
		}
		$msg .= '信息';
		$this->assign('usercard', $userinfo['usercard']);
		$this->assign("pic", $pic);
		$this->assign("change", $change);
		$this->assign("msg", $msg);
		return $this->fetch('search_new');
	}
	public function correct_errors() {
		$this->webtitle('图像勘误');
		$type = input("type"); //1不是本人 2闭眼
		$type = empty($type) ? 1 : $type;
		$userinfo = Db::name("information")->where(['id' => $this->user['user_id']])->find();
		if ($userinfo['view_subscribe'] == 0 && $userinfo['view_voluntarily'] == 0) {
			$this->redirect('index');
		}
		$this->checkimg($userinfo);
		$error = Db::name('picerror')->where('openid', $this->user['openid'])->find();
		if ($error) {
			$this->redirect('index/search');
		}
		$exchange = Db::name("change")->where('card1|card2', $userinfo['usercard'])->where('status=0 or status=2')->find();
		if ($exchange) {
			$this->redirect('index/search');
		}
		$userinfo['userimg'] = get_img($userinfo);
		$this->assign("user", $userinfo);
		return $this->fetch();
	}
	public function retarking() {
		$this->webtitle('照片补拍');
		$type = input("type"); //1不是本人 2闭眼
		$type = empty($type) ? 1 : $type;
		$userinfo = Db::name("information")->where(['id' => $this->user['user_id']])->find();
		$this->checkimg($userinfo);
		$error = Db::name('picerror')->where('openid', $this->user['openid'])->find();
		if ($error) {
			if ($error['status'] != '9') {
				$this->redirect('index/search');
			}
		}
		$exchange = Db::name("change")->where('user_id', $userinfo['usercard'])->where('status', 0)->find();
		if ($exchange) {
			$this->redirect('index/search');
		}
		$userinfo['userimg'] = get_img($userinfo);
		$this->assign("user", $userinfo);
		return $this->fetch();
	}
	public function confirm() {
		Db::name('information')->where(['id' => $this->user['user_id']])->update(['is_confirm' => 1]);
		return $this->return_msg('信息确认成功');
	}
	public function self_patting() {
		$this->webtitle('自行补拍');
		$userinfo = Db::name("information")->where(['id' => $this->user['user_id']])->find();
		$this->checkimg($userinfo);
		if ($userinfo['view_voluntarily'] != 1) {
			$this->assign('msg', '暂无自行补拍权限');
			return $this->fetch('self_role');
		}
		$pid = Db::name('picerror')->where('openid', $this->user['openid'])->find();

		if ($pid) {
			if ($pid['status'] != '9' && $pid['status'] != 4) {
				$this->redirect('index/search');
			}
		}

		$exchange = Db::name("change")->where('user_id', $userinfo['usercard'])->where('status', 0)->find();
		if ($exchange) {
			$this->redirect('index/search');
		}
		if (request()->isAjax()) {
			if ($pid) {$data['id'] = $pid['id'];};
			$data['picsrc'] = input("img");
			$data['create_time'] = time();
			$data['mobile'] = $userinfo['mobile'];
			$data['type'] = 1;
			$data['status'] = empty($userinfo['ps_number']) ? 4 : 1;
			$data['openid'] = $this->user['openid'];
			$data['mobile'] = $userinfo['mobile'];
			if (input("type") == 1) {
				$data['error_type'] = "图像不是本人";
			}
			if (input("type") == 2) {
				$data['error_type'] = "闭眼";
			}
			$need_send = input('need_send');
			$Pic = new Picerror();
			$return = $Pic->_save($data);
			if ($return) {
				if (empty($userinfo['ps_number'])) {
					return $this->return_msg(['status' => 2, 'msg' => '提交成功,请缴费否则无法通过审核'], 0);
				} else {
					return $this->return_msg(['status' => 1, 'msg' => '提交成功,请耐心等待管理审核'], 0);
				}

			} else {
				return $this->return_msg('提交失败，请稍后尝试', 1);
			}
		}
		if ($pid) {
			switch ($pid['status']) {
			case 1:
				return $this->return_msg('图像审核中', 'suc');
				break;
			case 2:
				return $this->return_msg('图像审核通过，正在制作中', 'suc');
				break;
			case 3:
				return $this->return_msg('您的照片已邮寄', 'suc');
				break;
			case 4:
				return $this->return_msg('请缴费否则无法通过审核', 'suc', url('pay'), '去缴费');
				break;
			}
		}

		return $this->fetch();
	}

	public function appointment() {
		$this->webtitle('预约补拍');
		$userinfo = Db::name("information")->where(['id' => $this->user['user_id']])->find();
		$this->checkimg($userinfo);
		if ($userinfo['view_subscribe'] != 1) {
			$this->assign('msg', '暂无预约补拍权限');
			return $this->fetch('self_role');
		}
		$is_appiont = Db::name('picerror')->where('openid', $this->user['openid'])->find();
		if ($is_appiont) {

			$this->redirect('index/search');

		}
		if ($is_appiont) {
			switch ($is_appiont['status']) {
			/*
				case 1:
					return $this->return_msg('已预约或已自行补拍', 'suc');
					break;
				case 2:
					return $this->return_msg('图像审核通过，正在制作中',  'suc');
					break;
				case 3:
					return $this->return_msg('您的照片已邮寄',  'suc');
					break;
			*/
			case 4:
				return $this->return_msg('请缴费否则无法通过审核', 'suc', url('pay'), '去缴费');
				break;
			case 11:
				return $this->return_msg('已预约成功，请勿重复提交补拍或预约', 'suc');
				break;
			default:
				$this->redirect('index/index/search');
				exit;
				break;
			}
		}
		$this->assign('start', date('Y-m-d'));
		$this->assign('end', date('Y-m-d', strtotime("+1months")));
		$this->assign("user", $userinfo);
		if (request()->isAjax()) {
			$post = input();
			$data['mobile'] = empty($post['mobile']) ? $userinfo['mobile'] : $post['mobile'];
			$data['note'] = $post['note'];
			$data['openid'] = $this->user['openid'];
			$data['status'] = empty($userinfo['ps_number']) ? 4 : 1;
			$data['need_send'] = $post['need_send']==1?1:2;
			$data['addr'] = $post['addr'];
			$data['appoint_time'] = strtotime($post['appoint_time']);
			if ((date('w', $data['appoint_time']) == 6) || (date('w', $data['appoint_time']) == 0)) {

				return $this->return_msg('提交失败，预约时间不能为周六周日', 1);
			}

			if ($post['type'] == 1) {
				$data['error_type'] = "图像不是本人";
			}
			if ($post['type'] == 2) {
				$data['error_type'] = "闭眼";
			}

			$data['type'] = 2;
			$Picerror = new Picerror();
			$return = $Picerror->_save($data);
			if ($return) {
				if (empty($userinfo['ps_number'])) {
					return $this->return_msg(['status' => 2, 'msg' => '预约成功,请缴费否则无法通过审核'], 0);
				} else {
					return $this->return_msg(['status' => 1, 'msg' => '预约成功,请在预约日期到达拍摄地点进行拍摄'], 0);
				}
			} else {
				return $this->return_msg('提交失败，请稍后尝试', 1);
			}

		}
		if (input('confirm') != 1) {
			$this->webtitle('预约补拍注意事项');
			return $this->fetch('appointment_msg');
		} else {
			$this->webtitle('预约补拍');
			return $this->fetch();
		}

	}
	public function exchange() {
		$this->webtitle('图像交换');
		if (request()->IsAjax()) {
			ignore_user_abort(true);
			$usercard = trim(input('usercard'));
			if (idcard_checksum18($usercard)) {
				if (!empty(session('change_time'))) {
					$time = session('change_time');
					if ((time() - $time) < 60) {
						return $this->return_msg('一分钟内只能修改一次', 1);
					}
				}
				$id = $this->user['user_id'];

				$webconfig = cache("webconfig");
				$user1 = Db::name('information')->where('id', $id)->find();
				//检测是否有审核中信息
				if (Db::name("change")->where("card1", $user1['usercard'])->where("status", 0)->find()) {
					return $this->return_msg('已有交换信息正在审核中，请勿再次提交', 1);
				}
				//检测是否有超过次数
				$times = Db::name("change")->where("card1", $user1['usercard'])->count();
				if ($times >= $webconfig['change_times']) {
					return $this->return_msg('提交次数超过限定，禁止提交', 1);
				}
				if (trim($user1['usercard']) == trim($usercard)) {
					return $this->return_msg('请勿填写您自己的身份证号码', 1);
				}
				$img1 = get_img($user1);
				$user2 = Db::name('information')->where('usercard', $usercard)->find();
				if ($user2['is_confirm'] == 1) {
					return $this->return_msg('对方图像已确认', 1);
				}
				if ($webconfig['is_repeat'] != 1) {
					//检测是否互换过
					if (DB::name('change')->where(['card1' => $user1['usercard'], 'card2' => $usercard])->find() || DB::name('change')->where(['card2' => $user1['usercard'], 'card1' => $usercard])->find()) {
						return $this->return_msg('双方已有互换记录，禁止再次互换', 1);
					}
				}

				$img2 = get_img($user2);
				if (!$img1 || !is_file('.' . $img1)) {
					return $this->return_msg('未检测到您的照片信息', 1);
				}
				if (!$img2 || !is_file('.' . $img2)) {
					return $this->return_msg('所提交身份证对应照片不存在！', 1);
				}
				$data['name1'] = $user1['user_name'];
				$data['name2'] = $user2['user_name'];
				$data['school1'] = $user1['schoolname'];
				$data['school2'] = $user2['schoolname'];
				$data['class1'] = $user1['class'];
				$data['class2'] = $user2['class'];
				$data['uniqid2'] = '';
				$data['card1'] = $user1['usercard'];
				$data['card2'] = $user2['usercard'];
				$data['create_time'] = time();
				$data['status'] = 0;
				$data['openid'] = $this->user['openid'];
				$data['user_id'] = $this->user['user_id'];
				$change = new Change();
				session('change_time', time());
				if ($change->_save($data)) {
					return $this->return_msg('已提交,请耐心等待管理审核,审核通过会自动更换照片');
					session('change_time', time());
				} else {
					return $this->return_msg('提交失败，请稍后尝试', 1);
				}
			} else {
				return $this->return_msg('身份证号码错误,请重新提交', 1);
			}
		}
            	if (empty(cache("other"))) {
			cache("other", Db::name("other")->find());
		}
		$msg = cache('other');
		$this->assign('msg', $msg);
		return $this->fetch();
	}

}
