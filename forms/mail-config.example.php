<?php

/**
 * cPanel → Email Accounts → Connect Devices → Mail Client Manual Settings
 * Secure SSL/TLS settings for rglsys@rgl.com.ph
 */
return [
    // Free at https://web3forms.com — register with info@rgl.com.ph, then paste your Access Key here.
    // Recommended: bypasses hosting bot protection that blocks forms/get-a-quote.php.
    'web3forms_access_key' => '',

    'to_email' => 'info@rgl.com.ph',
    'from_email' => 'rglsys@rgl.com.ph',
    'from_name' => 'RGL Website',

    'smtp_host' => 'rgl.com.ph',
    'smtp_port' => 465,
    'smtp_secure' => 'ssl',
    'smtp_username' => 'rglsys@rgl.com.ph',
    'smtp_password' => 'YOUR_EMAIL_PASSWORD_HERE',
];
