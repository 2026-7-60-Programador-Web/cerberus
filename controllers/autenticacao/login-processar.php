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
