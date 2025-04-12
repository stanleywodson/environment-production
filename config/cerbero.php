<?php

return [
	'authentication' => env('CERBERO_AUTHENTICATION', true),
	'jwk_url' => env('CERBERO_JWK_URL', 'https://apps.dataverso.net/api/public-jwks'),
	'secret' => env('CERBERO_SECRET', '01ac7e43e61c47e46674f9b7b3f5b497ffc1fe27b18793881a9da1d545613328'),
	'login_url' => env('CERBERO_LOGIN_URL', 'https://192.168.10.249:8443/api/auth/login')
];
