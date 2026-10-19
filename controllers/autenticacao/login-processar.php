<?php

session_start();

$usuariosCadastrados = [
    [
        "nome" => "Pedro Leandro",
        "email" => "pedro@email.com",
        "senha" => "12345abc",
        "admin" => true
    ],
    [
        "nome" => "Emily Vitória",
        "email" => "emily@email.com",
        "senha" => "12345abc",
        "admin" => false
    ],
    [
        "nome" => "Hellison Soares",
        "email" => "enzo@email.com",
        "senha" => "12345abc",
        "admin" => false
    ],
    [
        "nome" => "Amaury Damasceno",
        "email" => "amaury@email.com",
        "senha" => "12345abc",
        "admin" => true
    ]
];

$mensagem = "";

$email = filter_input(INPUT_POST, "email", FILTER_VALIDATE_EMAIL);
$senha = $_POST["senha"] ?? "";

/* ---------- administradores de clubes (banco de dados) ----------
   Se o e-mail for de um administrador de clube, entra na área do clube.
   Caso contrário (ou se o banco ainda não estiver configurado), segue o
   login que já existia, sem nenhuma alteração. */
if($email && is_file(__DIR__ . "/../../config/database.php")){
    try {
        require_once __DIR__ . "/../../core/clube-acesso.php";
        require_once __DIR__ . "/../../core/conexao.php";
        require_once __DIR__ . "/../../models/clubes-dados.php";

        $loginClube = adminClubeAutenticar($email, $senha);

        if($loginClube["ok"]){
            clubeAbrirSessao($loginClube["admin"], $loginClube["clube_nome"]);
            header("Location: ../../views/clube/painel.php");
            exit;
        }

        if($loginClube["erro"] !== ""){
            $_SESSION["mensagem"] = $loginClube["erro"];
            header("Location: ../../views/autenticacao/login.php");
            exit;
        }
    } catch (PDOException $e) {
        // tabelas do módulo ainda não criadas: ignora e usa o login antigo
    }
}

if(!$email){
    $mensagem = "Erro ao entrar: e-mail inválido";
}

$usuarioEncontrado = null;

foreach($usuariosCadastrados as $usuario){

    if($usuario["email"] === $email && $usuario["senha"] === $senha){
       $usuarioEncontrado = $usuario;
    }

}

if($usuarioEncontrado === null){
    $mensagem = "Erro ao entrar : email e/ou senha inválidos!";
}

//se deu tudo errado, existe uma mensagem preenchida (não vazia)
if(!empty($mensagem)){
    $_SESSION["mensagem"] = $mensagem;
    header("Location: ../../views/autenticacao/login.php");
    exit;
}

$_SESSION["email"] = $usuarioEncontrado["email"];
$_SESSION["admin"] = $usuarioEncontrado["admin"];
$_SESSION["nome"]  = $usuarioEncontrado["nome"];

//se deu tudo certo, verifica se é administrador ou cliente e redireciona
if($_SESSION["admin"] === true){
    header("Location: ../../views/administrador/painel.php");
} else {
    header("Location: ../../views/associados/painel.php");
}
exit;
