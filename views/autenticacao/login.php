<?php

session_start();

$mensagem = $_SESSION["mensagem"] ?? null;
unset($_SESSION["mensagem"]);


if(!empty($_SESSION["clube_id"])){
    header("Location: ../clube/painel.php");
    exit;
}

if(isset($_SESSION["email"])){
    if($_SESSION["admin"] === true){
        header("Location: ../administrador/painel.php");
    } else {
        header("Location: ../associados/painel.php");
    }
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Cerberus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body class="login-pagina">

    <!-- botão de tema discreto, no canto da tela -->
    <button id="btnTema" type="button" class="btn btn-tema login-tema" title="Alternar tema">🌙</button>

    <main class="login-container">

        <img src="../../assets/img/logo-cerberus-tres-lobos.svg" alt="Logo Cerberus" class="login-logo">
        <h1 class="login-marca">Cerberus</h1>
        <p class="login-subtitulo">Gestão de associados e convidados</p>

        <div class="card login-card">
            <div class="card-body p-4">
                <h2 class="h5 mb-4 text-center">Entrar</h2>

                <?php if($mensagem): ?>
                    <div class="alert login-alerta py-2" role="alert">
                        <i class="bi bi-exclamation-circle me-1"></i>
                        <?= htmlspecialchars($mensagem); ?>
                    </div>
                <?php endif; ?>

                <form action="../../controllers/autenticacao/login-processar.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" value="emily@email.com|pedro@email.com" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="senha" class="form-label">Senha</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="senha" name="senha" value="12345abc" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login w-100">Entrar</button>

                   <p style = "font-size:13px; padding-top:8px;" >Ainda não é cadastrado?<a href="../clube/cadastro.php"> Cadastre seu clube aqui!</a></p>
                </form>

            </div>
        </div>
    </main>

    <script src="../../assets/js/script.js"></script>
</body>
</html>