<?php
// Recebe o formulário "Cadastro dos sócios" (área do administrador), valida e grava na tabela "socios".

session_start();

if(!isset($_SESSION["email"]) || ($_SESSION["admin"] ?? false) !== true){
    header("Location: ../../views/autenticacao/login.php");
    exit;
}

$voltar = "../../views/administrador/cadastro-socios.php";

if($_SERVER["REQUEST_METHOD"] !== "POST"){
    header("Location: $voltar");
    exit;
}

/* ---------- funções de apoio ---------- */
function soDigitos(string $v): string {
    return preg_replace("/\D/", "", $v);
}

function cpfValido(string $cpf): bool {
    if(strlen($cpf) !== 11 || preg_match("/^(\d)\1{10}$/", $cpf)){
        return false;
    }
    for($t = 9; $t < 11; $t++){
        $soma = 0;
        for($i = 0; $i < $t; $i++){
            $soma += (int)$cpf[$i] * (($t + 1) - $i);
        }
        $digito = ((10 * $soma) % 11) % 10;
        if((int)$cpf[$t] !== $digito){
            return false;
        }
    }
    return true;
}

function dataValida(string $data): bool {
    $d = DateTime::createFromFormat("Y-m-d", $data);
    return $d && $d->format("Y-m-d") === $data;
}

/* ---------- leitura dos campos ---------- */
$dados = [
    "nome"            => trim($_POST["nome"] ?? ""),
    "data_nascimento" => trim($_POST["data_nascimento"] ?? ""),
    "genero"          => trim($_POST["genero"] ?? ""),
    "estado_civil"    => trim($_POST["estado_civil"] ?? ""),
    "profissao"       => trim($_POST["profissao"] ?? ""),
    "cpf"             => soDigitos($_POST["cpf"] ?? ""),
    "rg"              => trim($_POST["rg"] ?? ""),
    "orgao_expedidor" => trim($_POST["orgao_expedidor"] ?? ""),
    "telefone"        => soDigitos($_POST["telefone"] ?? ""),
    "email"           => trim($_POST["email"] ?? ""),
    "cep"             => soDigitos($_POST["cep"] ?? ""),
    "logradouro"      => trim($_POST["logradouro"] ?? ""),
    "numero"          => trim($_POST["numero"] ?? ""),
    "complemento"     => trim($_POST["complemento"] ?? ""),
    "bairro"          => trim($_POST["bairro"] ?? ""),
    "cidade"          => trim($_POST["cidade"] ?? ""),
    "uf"              => strtoupper(trim($_POST["uf"] ?? "")),
    "plano"           => trim($_POST["plano"] ?? ""),
    "data_inicio"     => trim($_POST["data_inicio"] ?? ""),
    "dia_vencimento"  => trim($_POST["dia_vencimento"] ?? ""),
    "contato_emergencia_nome"     => trim($_POST["contato_emergencia_nome"] ?? ""),
    "contato_emergencia_telefone" => soDigitos($_POST["contato_emergencia_telefone"] ?? ""),
    "observacoes"     => trim($_POST["observacoes"] ?? ""),
];

// o que o formulário deve mostrar de novo se houver erro (os campos digitados não se perdem)
$_SESSION["cadastro_socio_dados"] = $_POST;

/* ---------- validação ---------- */
$erros = [];

$obrigatorios = [
    "nome" => "Nome completo", "data_nascimento" => "Data de nascimento", "estado_civil" => "Estado civil",
    "profissao" => "Profissão", "cpf" => "CPF", "rg" => "RG", "orgao_expedidor" => "Órgão expedidor",
    "telefone" => "Telefone", "email" => "E-mail", "cep" => "CEP", "logradouro" => "Logradouro",
    "numero" => "Número", "bairro" => "Bairro", "cidade" => "Cidade", "uf" => "UF", "plano" => "Plano",
    "data_inicio" => "Início do contrato", "dia_vencimento" => "Dia de vencimento da mensalidade",
    "contato_emergencia_nome" => "Nome do contato de emergência",
    "contato_emergencia_telefone" => "Telefone do contato de emergência",
];
foreach($obrigatorios as $campo => $rotulo){
    if($dados[$campo] === ""){
        $erros[] = "Preencha o campo \"$rotulo\".";
    }
}

if(empty($erros)){
    if(mb_strlen($dados["nome"]) < 5 || strpos($dados["nome"], " ") === false){
        $erros[] = "Informe o nome completo (nome e sobrenome).";
    }
    if(!dataValida($dados["data_nascimento"]) || $dados["data_nascimento"] > date("Y-m-d") || $dados["data_nascimento"] < date("Y-m-d", strtotime("-120 years"))){
        $erros[] = "Data de nascimento inválida.";
    }
    if(!cpfValido($dados["cpf"])){
        $erros[] = "CPF inválido.";
    }
    if(!filter_var($dados["email"], FILTER_VALIDATE_EMAIL)){
        $erros[] = "E-mail inválido.";
    }
    if(!in_array(strlen($dados["telefone"]), [10, 11], true)){
        $erros[] = "Telefone inválido: informe DDD + número.";
    }
    if(!in_array(strlen($dados["contato_emergencia_telefone"]), [10, 11], true)){
        $erros[] = "Telefone do contato de emergência inválido: informe DDD + número.";
    }
    if(strlen($dados["cep"]) !== 8){
        $erros[] = "CEP inválido: deve ter 8 números.";
    }
    if(!preg_match("/^[A-Z]{2}$/", $dados["uf"])){
        $erros[] = "UF inválida: use 2 letras (ex.: CE).";
    }
    if(!in_array($dados["estado_civil"], ["Solteiro(a)", "Casado(a)", "União estável", "Separado(a)", "Divorciado(a)", "Viúvo(a)"], true)){
        $erros[] = "Escolha um estado civil válido.";
    }
    if($dados["genero"] !== "" && !in_array($dados["genero"], ["Feminino", "Masculino", "Outro", "Prefiro não informar"], true)){
        $erros[] = "Escolha um gênero válido ou deixe em branco.";
    }
    if(!in_array($dados["plano"], ["Titular", "Família"], true)){
        $erros[] = "Escolha um plano válido.";
    }
    if(!dataValida($dados["data_inicio"])){
        $erros[] = "Data de início do contrato inválida.";
    }
    if(!ctype_digit($dados["dia_vencimento"]) || (int)$dados["dia_vencimento"] < 1 || (int)$dados["dia_vencimento"] > 28){
        $erros[] = "O dia de vencimento deve ficar entre 1 e 28.";
    }
}

if(!empty($erros)){
    $_SESSION["cadastro_socio_erros"] = $erros;
    header("Location: $voltar");
    exit;
}

/* ---------- gravação ---------- */
require_once __DIR__ . "/../../core/conexao.php";

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "INSERT INTO socios
            (nome, data_nascimento, genero, estado_civil, profissao, cpf, rg, orgao_expedidor, telefone, email,
             cep, logradouro, numero, complemento, bairro, cidade, uf, plano, data_inicio, dia_vencimento,
             contato_emergencia_nome, contato_emergencia_telefone, observacoes)
         VALUES
            (:nome, :data_nascimento, :genero, :estado_civil, :profissao, :cpf, :rg, :orgao_expedidor, :telefone, :email,
             :cep, :logradouro, :numero, :complemento, :bairro, :cidade, :uf, :plano, :data_inicio, :dia_vencimento,
             :contato_emergencia_nome, :contato_emergencia_telefone, :observacoes)"
    );
    $stmt->execute([
        "nome" => $dados["nome"], "data_nascimento" => $dados["data_nascimento"],
        "genero" => $dados["genero"] !== "" ? $dados["genero"] : null,
        "estado_civil" => $dados["estado_civil"], "profissao" => $dados["profissao"],
        "cpf" => $dados["cpf"], "rg" => $dados["rg"], "orgao_expedidor" => $dados["orgao_expedidor"],
        "telefone" => $dados["telefone"], "email" => $dados["email"],
        "cep" => $dados["cep"], "logradouro" => $dados["logradouro"], "numero" => $dados["numero"],
        "complemento" => $dados["complemento"] !== "" ? $dados["complemento"] : null,
        "bairro" => $dados["bairro"], "cidade" => $dados["cidade"], "uf" => $dados["uf"],
        "plano" => $dados["plano"], "data_inicio" => $dados["data_inicio"], "dia_vencimento" => (int)$dados["dia_vencimento"],
        "contato_emergencia_nome" => $dados["contato_emergencia_nome"],
        "contato_emergencia_telefone" => $dados["contato_emergencia_telefone"],
        "observacoes" => $dados["observacoes"] !== "" ? $dados["observacoes"] : null,
    ]);

    // a matrícula é o número do cadastro com 6 dígitos (ex.: 000009)
    $id = (int)$pdo->lastInsertId();
    $matricula = str_pad((string)$id, 6, "0", STR_PAD_LEFT);
    $pdo->prepare("UPDATE socios SET matricula = :m WHERE id = :id")->execute(["m" => $matricula, "id" => $id]);

    $pdo->commit();
} catch (PDOException $e) {
    if($pdo->inTransaction()){
        $pdo->rollBack();
    }
    if($e->getCode() === "23000"){
        $_SESSION["cadastro_socio_erros"] = ["Já existe um sócio cadastrado com este CPF ou e-mail."];
    } elseif($e->getCode() === "42S02"){
        $_SESSION["cadastro_socio_erros"] = ["A tabela \"socios\" ainda não existe no banco. Rode o arquivo database/socios.sql e tente de novo."];
    } else {
        $_SESSION["cadastro_socio_erros"] = ["Não foi possível salvar o cadastro. Tente novamente."];
    }
    header("Location: $voltar");
    exit;
}

unset($_SESSION["cadastro_socio_dados"]);
$_SESSION["cadastro_socio_sucesso"] = "Sócio {$dados['nome']} cadastrado com sucesso! Matrícula: $matricula.";
header("Location: $voltar");
exit;
