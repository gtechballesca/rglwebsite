<?php
/**
 * SET ASIDE — reCAPTCHA is currently disabled in production.
 *
 * To re-enable later:
 * 1. Copy this file to recaptcha-config.php and paste your keys
 * 2. Add data-recaptcha-site-key="SITE_KEY" on the inquiry form in index.html
 * 3. Load: <script src="https://www.google.com/recaptcha/api.js?render=SITE_KEY"></script>
 * 4. Re-add server-side verification in get-a-quote.php (see git history)
 *
 * Create keys at: https://www.google.com/recaptcha/admin
 * Choose: reCAPTCHA v3
 * Domains: rgl.com.ph, www.rgl.com.ph
 */
return [
    'site_key' => 'YOUR_RECAPTCHA_SITE_KEY',
    'secret_key' => 'YOUR_RECAPTCHA_SECRET_KEY',
    'min_score' => 0.5,
    'expected_action' => 'inquiry_submit',
];
