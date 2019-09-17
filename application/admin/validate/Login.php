<?php
namespace app\admin\validate;
use think\Validate;

class Login extends Validate {

	protected $rule = [
		'username' => 'require',
		'password' => "require",
		//  'captcha|验证码'=>'require|captcha'
	];
	protected $message = [
		'username' => '用户名不能为空',
		'password' => '密码不能为空',
		//  'captcha.require' =>'验证码必须'
	];

/*    // 自定义验证规则
protected function checkName($value,$rule,$data)
{
return $rule == $value ? true : '名称错误';
}*/
}