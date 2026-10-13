<?php

session_start();

session_destroy();

header("Location: ../views/autenticacao/login.php");
exit;
