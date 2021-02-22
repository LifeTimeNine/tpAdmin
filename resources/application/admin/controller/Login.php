<?php

namespace app\admin\controller;

use tpadmin\Controller;
use tpadmin\model\SystemUser;
use tpadmin\service\Admin;
use tpadmin\service\Captcha;
use tpadmin\tools\Data;

/**
 * 登录
 * Class Login
 */
class Login extends Controller
{

    /**
     * 登录
     */
    public function index()
    {
        if ($this->request->isGet())
        {
            if (Admin::instance()->isLogin()) {
                $this->redirect('@admin');
            }
            $this->title = '系统登录';
            $this->captcha_type = 'logo_captcha';
            $this->captcha_token = Data::uniqidDateCode();
            $this->app->session->set($this->captcha_type, $this->captcha_token);
            return $this->fetch();
        } else {
            if (empty(input('verify')) || empty(input('uniqid'))) {
                $this->error('验证码验证失败，请重新输入！');
            }
            if (!Captcha::instance()->check(input('verify'), input('uniqid'))) {
                $this->error('验证码验证失败，请重新输入！');
            }
            $login = SystemUser::login(input('username'), input('password'), input('uniqid'));
            if ($login !== true) {
                $this->error($login);
            }
            $this->success('登录成功', url('@admin'));
        }
    }

    /**
     * 退出登录
     */
    public function out()
    {
        SystemUser::loginout();
        $this->success('退出登录成功', url('@admin/login'));
    }

    public function captcha()
    {
        $captcha = Captcha::instance()->init();
        $this->type = input('type', 'captcha-type');
        $this->token = input('token', 'captchat-token');
        $data = [
            'image' => $captcha->getData(),
            'uniqid' => $captcha->getUniqid()
        ];
        if ($this->app->session->get($this->type) === $this->token)
        {
            $data['code'] = $captcha->getCode();
            $this->app->session->delete($this->type);
        }
        $this->success('获取成功', $data);
    }
}