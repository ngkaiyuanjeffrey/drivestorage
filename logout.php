<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
sign_out();
redirect('login.php');
