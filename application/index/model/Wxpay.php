<?php
/**
 * Created by PhpStorm.
 * User: Mikkle
 * Email:776329498@qq.com
 * Date: 2017/2/4
 * Time: 15:48
 */

namespace app\index\model;
use mikkle\tp_wxpay\JsApi_pub as JaApi;
use mikkle\tp_wxpay\UnifiedOrder_pub as UnifiedOrder;
use think\Db;
/**
 * 微信支付帮助库
 * ====================================================
 * 命名空间 com\wxpay\+类名+_pub
 * 接口分三种类型：
 * 【请求型接口】--Wxpay_client_
 * 		统一支付接口类--UnifiedOrder
 * 		订单查询接口--OrderQuery
 * 		退款申请接口--Refund
 * 		退款查询接口--RefundQuery
 * 		对账单接口--DownloadBill
 * 		短链接转换接口--ShortUrl
 * 【响应型接口】--Wxpay_server_
 * 		通用通知接口--Notify
 * 		Native支付——请求商家获取商品信息接口--NativeCall
 * 【其他】
 * 		静态链接二维码--NativeLink
 * 		JSAPI支付--JsApi
 * =====================================================
 * 【CommonUtil】常用工具：
 * 		trimString()，设置参数时需要用到的字符处理函数
 * 		createNoncestr()，产生随机字符串，不长于32位
 * 		formatBizQueryParaMap(),格式化参数，签名过程需要用到
 * 		getSign(),生成签名
 * 		arrayToXml(),array转xml
 * 		xmlToArray(),xml转 array
 * 		postXmlCurl(),以post方式提交xml到对应的接口url
 * 		postXmlSSLCurl(),使用证书，以post方式提交xml到对应的接口url
 */

use think\Model;

class Wxpay extends Model {
	protected $notify_url = '/index/wechatuser/notify'; //自己定义
	protected $order_table_param = [
		'table' => 'yhc_orders', //订单表名称
		'no_field' => 'oid', //订单号 字段名字
		'state_field' => 'status', //订单支付状态值字段名
		'amount_field' => 'money', //订单金额值字段名
		'pay_ok' => '1', //订单已支付状态值
		'pay_no' => '0', // 订单未支付状态值
		'map' => [['status' => 0]], //其他订单是否可以支付的参数值
	];
	protected $wx_config = [
		'wechat_appid' => 'wx4a5707b193c85803', //微信公众号身份的唯一标识。审核通过后，在微信发送的邮件中查看
		'wechat_mchid' => '1491963972', //受理商ID，身份标识 商户号
		'wechat_appkey' => 'e0bc9715dc907b139dfcb6ac6960082a', //商户支付密钥Key。审核通过后，在微信发送的邮件中查看
		'wechat_appsecret' => '00786af3a606448490f235642a3d38bc', //
		'sslcert_path' => '',
		'sslkey_path' => '',

	];
	public function _initialize() {
		ini_set('date.timezone', 'Asia/Shanghai');
	}
	/**
	 * 统一下单方法
	 * #User: Mikkle
	 * #Email:776329498@qq.com
	 * #Date:
	 * @param array $data
	 * @return bool
	 */
	protected function unifiedOrder($data = []) {
		$unifiedOrder = new UnifiedOrder();
		$unifiedOrder->setParameter("openid", $data['openid']); // openid
		$unifiedOrder->setParameter("body", '商品订单号'+$data['order_no']); // 商品描术
		$unifiedOrder->setParameter("out_trade_no", $data['order_no']); // 商户订单号
		$unifiedOrder->setParameter("total_fee", $data['amount'] * 100); // 总金额
		$unifiedOrder->setParameter("notify_url", 'http://' . $_SERVER['HTTP_HOST'] . $this->notify_url); // 通知地址 $this->notify_url自己定义
		$unifiedOrder->setParameter("trade_type", "JSAPI"); // 交易类型
		return $unifiedOrder->getPrepayId();
	}
	/**
	 * 获取JsApi$getParameters参数
	 * #User: Mikkle
	 * #Email:776329498@qq.com
	 * #Date:
	 * @param string $unified_order
	 * @return string
	 */
	protected function getParameters($unified_order = '') {
		$jsApi = new JaApi();
		$jsApi->setPrepayId($unified_order);
		$jsApiParameters = $jsApi->getParameters();
		return $jsApiParameters;
	}
	/**
	 * 根据订单号支付订单返回$getParameters参数
	 * #User: Mikkle
	 * #Email:776329498@qq.com
	 * #Date:
	 * @param string $order_no
	 * @param array $param
	 * @return array
	 */
	public function payByOrderNo($order_no = '2017020453102495', $param = []) {
		$param = $this->order_table_param;
		$order_info = Db::table($param['table'])
			->field(' ' . $param['no_field'] . ' , ' . $param['amount_field'] . ',openid ')
			->where($param['state_field'], '=', '0')
			->where([$param['no_field'] => $order_no])
			->find();
		if (!$order_info) {
			return ['code' => 1010, 'msg' => '订单不存在或者已经是完成状态'];
		}

		$data = [
			'order_no' => $order_no,
			'amount' => $order_info[$param['amount_field']],
			'openid' => $order_info['openid'],
		];
		$unified_order = $this->unifiedOrder($data); //统一下单
		$this->unified_order = $unified_order;
		$jsApiParameters = $this->getParameters($unified_order);
		return ['code' => 1001, 'order_no' => $order_no, 'jsApiParameters' => $jsApiParameters, 'amount' => $order_info[$param['amount_field']]];
	}

}