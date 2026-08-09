<?php
/**
 * Copy this file to recaptcha-config.php and paste your keys.
 *
 * Create keys at: https://www.google.com/recaptcha/admin
 * Choose: reCAPTCHA v3
 * Domains: rgl.com.ph, www.rgl.com.ph (add localhost for local tests)
 */
return [
    // Public — also paste the same value into index.html (data-recaptcha-site-key + api.js?render=)
    'site_key' => 'YOUR_RECAPTCHA_SITE_KEY',

    // Private — never put this in HTML or JS
    'secret_key' => 'YOUR_RECAPTCHA_SECRET_KEY',

    // 0.0 = bot … 1.0 = human. 0.5 is Google's usual starting point.
    'min_score' => 0.5,

    // Must match grecaptcha.execute(..., { action: '...' }) in validate.js
    'expected_action' => 'inquiry_submit',
];
