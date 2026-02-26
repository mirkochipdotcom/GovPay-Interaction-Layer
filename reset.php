<?php
require __DIR__ . '/vendor/autoload.php';
\App\Database\Connection::getPDO()->exec("UPDATE pendenze_massive SET stato='PENDING' WHERE stato='SUCCESS' OR stato='ERROR'");
echo "OK\n";
