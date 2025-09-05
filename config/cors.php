<?php

return [
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => ['*'],                 // o usa allowed_origins_patterns => ['.*']
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => false,            // con Bearer no necesitas cookies

];
