<?php
namespace app\admin\validate;
use think\Validate;

class Admin extends Validate {
	protected $rule = [
		'username' => 'require|unique:admin',
		'password' => "require",
		//  'captcha|验证码'=>'require|captcha'
	];
	protected $message = [
		'username.unique' => '用户名已经存在',
		'username.require' => '用户名不能为空',
		'password' => '密码不能为空',
		//  'captcha.require' =>'验证码必须'
	];
}