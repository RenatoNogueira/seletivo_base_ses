<?php
require_once 'includes/functions.php';

iniciarSessao();

// Destruir sessão
session_destroy();

// Redirecionar para página de login
redirecionar('index.php');
?>
