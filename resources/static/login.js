$(function() {
  window.$body = $('body');

  $body.find('[data-login-form]').map(function(that) {
    that = this;
    require(['md5'], function (md5) {
      $('form').vali(function (data) {
        data['password'] = md5.hash(md5.hash(data['password']) + data['uniqid']);
        $.form.load(location.href, data, 'post', function(ret) {
          if (ret.code != 1) {
            $(that).find('.verify.layui-hide').removeClass('layui-hide');
            $(that).find('[data-captcha]').trigger('click');
          }
        }, null, null, false);
      });
    })
  });
  
  $body.on('click', '[data-captcha]', function () {
    let $that = $(this),
    action = this.getAttribute('data-captcha') || window.location.href,
    type = this.getAttribute('data-captcha-type') || 'captcha-type',
    token = this.getAttribute('data-captcha-token') || 'captcha-token',
    uniqid = this.getAttribute('data-field-uniqid') || 'uniqid',
    verify = this.getAttribute('data-field-verify') || 'verify';
    $.form.load(action, {type: type, token: token}, 'post', function (ret) {
      if (ret.code) {
        $that.html('');
        $that.append($('<img alt="img" src="">').attr('src', ret.data.image));
        $that.append($('<input type="hidden">').attr('name', uniqid).val(ret.data.uniqid));
        if (ret.data.code) {
          $that.parents('form').find(`[name=${verify}]`).attr('value', ret.data.code);
        } else {
          $that.parents('form').find(`[name=${verify}]`).attr('value', '');
        }
        return false;
      }
    }, false);
  })

  $('[data-captcha]').map(function() {
    $(this).trigger('click');
  })
});