<?php
// Run locally; never upload the output to a public directory.
echo 'APP_KEY=base64:'.base64_encode(random_bytes(32)).PHP_EOL;
echo 'SETUP_TOKEN='.bin2hex(random_bytes(32)).PHP_EOL;
