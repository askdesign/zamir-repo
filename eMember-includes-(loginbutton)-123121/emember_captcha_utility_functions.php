<?php

function emember_recaptcha_html() {
    $emember_config = Emember_Config::getInstance();
    $enable_recaptcha = $emember_config->getValue('emember_enable_recaptcha');

    if ($enable_recaptcha) {
        $publickey = $emember_config->getValue('emember_recaptcha_public'); //SiteKey
        $captcha_code = '';
        $captcha_code .= '<div class="emember-recaptcha-section">';
        $captcha_code .= '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
        $captcha_code .= '<div class="emember_g_captcha">';
        $captcha_code .= '<div class="g-recaptcha" data-sitekey="' . $publickey . '"></div>';
        $captcha_code .= '</div>';
        $captcha_code .= '</div>'; //end .emember-recaptcha-section

        return $captcha_code;
    }
    return "";
}

function emember_recaptcha_verify() {
    $emember_config = Emember_Config::getInstance();
    $enable_recaptcha = $emember_config->getValue('emember_enable_recaptcha');

    $result = new stdClass();
    $result->valid = true;
    $result->message = "";
    if ($enable_recaptcha) {
        require_once(WP_EMEMBER_PATH . 'lib/captcha/autoload.php');
        $secret = $emember_config->getValue('emember_recaptcha_private'); //Secret

        try {
            //Initialize captcha object            
            $reCaptcha = new \ReCaptcha\ReCaptcha($secret);
        } catch (Exception $e) {
            echo 'Error! Failed to initialize reCAPTCHA class. The reCAPTCHA library needs PHP5.3+ on your server';
            eMember_log_debug("Error! Failed to initialize reCAPTCHA class. The reCAPTCHA library needs PHP5.3+ on your server.", false);
            return;
        }

        $resp = $reCaptcha->verify($_REQUEST["g-recaptcha-response"], $_SERVER["REMOTE_ADDR"]);
        if ($resp->isSuccess()) {
            //valid reCAPTCHA response.Go ahead with the registration
            $result->valid = true;
            $result->message = "";
            return $result;
        } else {
            //Invalid response. Stop going forward. Set the error msg so the form shows it to the user.
            //var_dump($resp);
            $result->valid = false;
            $result->message = "<p class='emember_error'><strong>" . EMEMBER_CAPTCHA_VERIFICATION_FAILED . "</strong></p>";
            return $result;
        }
    }

    return $result;
}