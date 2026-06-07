<?php
/**
 * Widget reCAPTCHA v2 (checkbox «No soy un robot») para formularios públicos.
 */
$captchaKey = (defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY !== '')
    ? trim((string) RECAPTCHA_SITE_KEY)
    : '';
if ($captchaKey === '') {
    return;
}
?>
<div class="am-captcha-widget mb-3">
    <div class="g-recaptcha" data-sitekey="<?php echo esc($captchaKey); ?>"></div>
</div>
