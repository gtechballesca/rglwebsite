<?php
/**
 * Legacy contact handler — disabled.
 * The live site uses forms/get-a-quote.php.
 */
header('Content-Type: text/plain; charset=UTF-8');
http_response_code(410);
echo 'This endpoint is no longer in use. Please use the inquiry form on https://rgl.com.ph/ or email info@rgl.com.ph.';
