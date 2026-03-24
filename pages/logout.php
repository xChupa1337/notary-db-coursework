<?php
require_once __DIR__ . '/../includes/config.php';
logoutUser();
redirect('/notary/pages/login.php');
