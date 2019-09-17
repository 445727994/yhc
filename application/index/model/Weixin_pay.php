<?php
namespace app\index\model;
use think\Db;
use think\Validate;
use think\Loader;
use think\Model;

class Weixin_pay extends model
{
	protected $appScrect;//微信公众平台的appscrect
	protected $appId;//微信公众平台appid
    protected $key;//微信商户平台配置的秘钥
	protected $mch_id;//微信商户号
	protected $values = array();

	public function __construct($appScrect="00786af3a606448490f235642a3d38bc",$appId="wx4a5707b193c85803", $key="e0bc9715dc907b139dfcb6ac6960082a",$mch_id ='1491963972'){
			$this->appScrect=$appScrect;
			$this->appId=$appId;
            $this->key=$key;
			$this->mch_id=$mch_id;

	}

	public function pay($openid,$total_fee,$body,$out_trade_no){
		$config= config('wxpay');
	    $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
	   	$notify_url   ='http://'.$_SERVER['HTTP_HOST'].'/index/base/notify';
        // $notify_url = "http://xiaokang.appudid.cn/index/common/notify";
		$onoce_str = $this->createNoncestr();
        $data["appid"] = $this->appId;
        $data["body"] = $body;
        $data["mch_id"] = $this->mch_id;//商户号
        $data["nonce_str"] = $onoce_str;
        $data["notify_url"] = $notify_url;
        $data["out_trade_no"] = $out_trade_no;
        $data["spbill_create_ip"] = $this->get_client_ip();
        $data["total_fee"] = $total_fee;
        $data["trade_type"] = "JSAPI";
        $data["openid"] = $openid;
        $sign = $this->getSign($data);
        $data["sign"] = $sign;
        $xml = $this->arrayToXml($data);
        $response = $this->postXmlCurl($xml, $url);
        //将微信返回的结果xml转成数组
        $response = $this->xmlToArray($response);
        if($response){
            if(isset($response['err_code'])){
                if($response['err_code']==='ORDERPAID'){
                    if($response['err_code_des']=='该订单已支付'){

                        return array('code'=>1,'msg'=>'该订单已支付');
                    }else{

                        return array('code'=>2,'msg'=>$response['err_code_des']);
                    }
                }
                return array('code'=>2,'msg'=>$response['err_code_des']);
            }


            
            $response['package']="prepay_id=".$response['prepay_id'];
            $jsapi=array();
            $timeStamp = time();

            /*if (!empty($response['prepay_id'])) {
                $response['package']="prepay_id=".$response['prepay_id'];
                $jsapi['package']=("prepay_id=" . $response['prepay_id']);
            }else{
                $response['package']="prepay_id=";
                $jsapi['package']=("prepay_id=");
            }


            if (!empty($response["appid"])) {
                $jsapi['appId']=($response["appid"]);   
            }else{
                $jsapi['appId']="";
            }*/

                //dump($response);die;
            $jsapi['appId']=($response["appid"]);   
            $jsapi['timeStamp']=("$timeStamp");
            $jsapi['nonceStr']=($this->createNoncestr());
            $jsapi['package']=("prepay_id=" . $response['prepay_id']);
            $jsapi['signType']=("MD5");
            $jsapi['paySign']=($this->getSign($jsapi));
            $parameters = json_encode($jsapi);
            // halt($jsapi);
                //请求数据,统一下单
            //dump($parameters);die;  
            return $parameters; 
            // return 2342423;
        }

     
	}



    public function payh5($openid,$total_fee,$body,$out_trade_no){
          $config= config('wxpay');
            $ip= request()->ip();
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $notify_url   ='http://'.$_SERVER['HTTP_HOST'].'/index/index/notify';
        $onoce_str = $this->createNoncestr();
        $data["appid"] = $this->appId;
        $data["body"] = $body;
        $data["mch_id"] = $this->mch_id;
        $data["nonce_str"] = $onoce_str;
        $data["notify_url"] = $notify_url;
        $data["out_trade_no"] = $out_trade_no;
        $data["spbill_create_ip"] = $ip;
        $data["total_fee"] = $total_fee;
        $data["trade_type"] = "MWEB";
        // $data["openid"] = $openid;
        $data["scene_info"] = "{'h5_info': {'type':'Wap','wap_url':  $notify_url,'wap_name': '缴费'}}";
        $sign = $this->getSign($data);
         // halt($data);
        $data["sign"] = $sign;

 
        $xml = $this->arrayToXml($data);
        $response = $this->postXmlCurl($xml, $url);

        //将微信返回的结果xml转成数组
        $response = $this->xmlToArray($response);
        

              
            //请求数据,统一下单  
                      
            return $response; 
    }

   


  




	public static function getNonceStr($length = 32)
	{
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {
			$str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
		}
		return $str;
	}

	// 　　/*生成签名*/
    public function getSign($Obj){
        foreach ($Obj as $k => $v){
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //echo '【string1】'.$String.'</br>';
        //签名步骤二：在string后加入KEY
        $String = $String."&key=".$this->key;
        //echo "【string2】".$String."</br>";
        //签名步骤三：MD5加密
        $String = md5($String);
        //echo "【string3】 ".$String."</br>";
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        //echo "【result】 ".$result_."</br>";
        return $result_;
    }
 
 
    /**
    *  作用：产生随机字符串，不长于32位
    */
    public function createNoncestr( $length = 32 ){
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789"; 
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  { 
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1); 
        } 
        return $str;
    }
 
 
    //数组转xml
    public function arrayToXml($arr){
        $xml = "<xml>";
        foreach ($arr as $key=>$val){
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">"; 
            }
        }
        $xml.="</xml>";
        return $xml;
    }
 
       
    /**
    *  作用：将xml转为array
    */
    public function xmlToArray($xml){  
        //将XML转为array       
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);   
        return $array_data;
    }
 
 
    /**
    *  作用：以post方式提交xml到对应的接口url
    */
    public function postXmlCurl($xml,$url,$second=30){  
        //初始化curl       
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
 
        if($data){
            curl_close($ch);
            return $data;
        }else{
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error"."<br>";
            curl_close($ch);
            return false;
        }
    }
 
 
    /*
    获取当前服务器的IP
    */
    public function get_client_ip(){
        if ($_SERVER['REMOTE_ADDR']) {
            $cip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv("REMOTE_ADDR")) {
            $cip = getenv("REMOTE_ADDR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
            $cip = getenv("HTTP_CLIENT_IP");
        } else {
            $cip = "unknown";
        }
        return $cip;
    }
     
 
    /**
    *  作用：格式化参数，签名过程需要使用
    */
    public function formatBizQueryParaMap($paraMap, $urlencode){
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v){
            if($urlencode){
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar;
        if (strlen($buff) > 0){
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }
        return $reqPar;
    }
	
		public function MakeSign($unifiedorder)
	{
		$this->values=$unifiedorder;
		//签名步骤一：按字典序排序参数
		// ksort($this->values);
		$string = $this->ToUrlParams();
//		halt($string);
		//签名步骤二：在string后加入KEY
		$string = $string . "&key=".$this->key;
		//签名步骤三：MD5加密
		$string = md5($string);
		//签名步骤四：所有字符转为大写
		$result = strtoupper($string);
		return $result;
	}

	public function ToUrlParams()
	{
		$buff = "";
		foreach ($this->values as $k => $v)
		{
			if($k != "sign" && $v != "" && !is_array($v)){
				$buff .= $k . "=" . $v . "&";
			}
		}

		$buff = trim($buff, "&");
		return $buff;
	}


		  function array2xml($array)
    {
        $xml='<xml>';
        foreach($array as $key=>$val){
            if(is_numeric($key)){
                $key="item id=\"$key\"";
            }else{
                //去掉空格，只取空格之前文字为key
                list($key,)=explode(' ',$key);
            } 
            $xml.="<$key>";
            $xml.=is_array($val)?$this->_array2xml($val):$val;
            //去掉空格，只取空格之前文字为key
            list($key,)=explode(' ',$key);
            $xml.="</$key>";
            
        }
            $xml.="</xml>";

        return $xml;
    }

       function xml2array($xml)
    {    
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);        
        return $values;
    }

	
    public  function request_post($url = '', $param = '')
    {
        if (empty($url) || empty($param)) {
            return false;
        }
        $postUrl = $url;
        $curlPost = $param;
        $ch = curl_init(); //初始化curl
        curl_setopt($ch, CURLOPT_URL, $postUrl); //抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0); //设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1); //post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch); //运行curl
        curl_close($ch);
        return $data;
    }

    function curl_post_ssl($url, $vars, $second=30,$aHeader=array())
	{
                $ch = curl_init();
                //curl_setopt($ch,CURLOPT_VERBOSE,'1');
                curl_setopt($ch,CURLOPT_TIMEOUT,$second);
                curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch,CURLOPT_URL,$url);
                curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
                curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
                curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
                curl_setopt($ch,CURLOPT_SSLCERT,'/data/cert/php.pem');
                curl_setopt($ch,CURLOPT_SSLCERTPASSWD,'1234');
                curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
                curl_setopt($ch,CURLOPT_SSLKEY,'/data/cert/php_private.pem');
 
                if( count($aHeader) >= 1 ){
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
                }
 
                curl_setopt($ch,CURLOPT_POST, 1);
                curl_setopt($ch,CURLOPT_POSTFIELDS,$vars);
                $data = curl_exec($ch);
                curl_close($ch);
                if($data){

                        return $data;

                }else{
                        return false;

                }
                   
	}
}