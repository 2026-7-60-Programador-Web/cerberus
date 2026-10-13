<?php

session_start();
require_once __DIR__ . "/../../models/portaria-dados.php";

// só administrador logado
if(!isset($_SESSION["email"]) || $_SESSION["admin"] !== true){
    header("Location: ../../views/autenticacao/login.php");
    exit;
}

if($_SERVER["REQUEST_METHOD"] !== "POST"){
    header("Location: ../../views/administrador/portaria.php");
    exit;
}

// aba para onde o funcionário volta depois da ação (só aceita as que existem)
$abasValidas = ["painel", "acessos", "visitantes", "veiculos"];
$aba = in_array($_POST["aba"] ?? "", $abasValidas, true) ? $_POST["aba"] : "painel";

// $pessoaRef (opcional): mantém a pessoa aberta na tela quando algo deu errado
function voltar(string $aba, string $tipo, array $textos, string $pessoaRef = ""): void {
    $_SESSION["msg_portaria"] = ["tipo" => $tipo, "textos" => $textos];

    $destino = "../../views/administrador/portaria.php?aba=" . $aba;

    if($pessoaRef !== ""){
        $destino .= "&pessoa=" . urlencode($pessoaRef);
    }

    header("Location: " . $destino);
    exit;
}

if(!csrfValido((string)($_POST["csrf"] ?? ""))){
    voltar($aba, "danger", ["A sessão expirou. Atualize a página e tente de novo."]);
}

$acao     = $_POST["acao"] ?? "";
$operador = $_SESSION["nome"] ?? "Portaria";
$conv     = carregarDados();
$pessoas  = montarPessoas($conv);

switch($acao){

    /* ================= PESSOAS: ENTRADA ================= */
    case "entrada":
        $ref    = (string)($_POST["ref"] ?? "");
        $pessoa = buscarPessoa($ref, $pessoas);

        if(!$pessoa){
            voltar($aba, "danger", ["Pessoa não encontrada."]);
        }

        $avaliacao = avaliarAcesso($pessoa, $conv);

        if(!$avaliacao["liberado"]){
            voltar($aba, "danger", array_merge(["Entrada não liberada para " . $pessoa["nome"] . "."], $avaliacao["motivos"]), $ref);
        }

        $erro = registrarEntradaPessoa($pessoa, $avaliacao, $operador);

        if($erro !== ""){
            voltar($aba, "danger", [$erro], $ref);
        }

        voltar($aba, "success", ["Entrada registrada: " . $pessoa["nome"] . " às " . date("H:i") . "."]);
        break;

    /* ================= PESSOAS: SAÍDA ================= */
    case "saida":
        $ref  = (string)($_POST["ref"] ?? "");
        $nome = "";
        $erro = registrarSaidaPessoa($ref, $operador, $nome);

        if($erro !== ""){
            voltar($aba, "danger", [$erro]);
        }

        voltar($aba, "success", ["Saída registrada: " . $nome . " às " . date("H:i") . "."]);
        break;

    /* ================= PESSOAS: NEGAR ENTRADA ================= */
    case "negar":
        $ref    = (string)($_POST["ref"] ?? "");
        $pessoa = buscarPessoa($ref, $pessoas);

        if(!$pessoa){
            voltar($aba, "danger", ["Pessoa não encontrada."]);
        }

        $motivo    = cortarTexto(trim((string)($_POST["motivo"] ?? "")), 120);
        $avaliacao = avaliarAcesso($pessoa, $conv);

        // se a pessoa já não estava liberada, o próprio sistema explica o motivo
        if($motivo === "" && !$avaliacao["liberado"]){
            $motivo = cortarTexto(implode(" ", $avaliacao["motivos"]), 120);
        }

        if($motivo === ""){
            voltar($aba, "danger", ["Informe o motivo para negar a entrada."], $ref);
        }

        $erro = registrarNegado($pessoa, $motivo, $operador);

        if($erro !== ""){
            voltar($aba, "danger", [$erro], $ref);
        }

        voltar($aba, "warning", ["Entrada negada e registrada: " . $pessoa["nome"] . "."]);
        break;

    /* ================= VEÍCULOS: CADASTRAR ================= */
    case "veiculo_cadastrar":
        $placa  = normalizarPlaca((string)($_POST["placa"] ?? ""));
        $modelo = cortarTexto(trim((string)($_POST["modelo"] ?? "")), 60);
        $dono   = buscarPessoa((string)($_POST["proprietario"] ?? ""), $pessoas);
        $erros  = [];

        if(!placaValida($placa)){
            $erros[] = "Placa inválida. Use o formato ABC-1234 ou ABC1D23.";
        }

        if(strlen($modelo) < 2){
            $erros[] = "Informe o modelo do veículo.";
        }

        // dependentes não são proprietários de veículo neste cadastro
        if(!$dono || $dono["tipo"] === "dependente"){
            $erros[] = "Selecione o proprietário do veículo.";
        }

        if(!empty($erros)){
            $_SESSION["form_veiculo"] = $_POST;
            voltar("veiculos", "danger", $erros);
        }

        $erro = cadastrarVeiculo($placa, $modelo, $dono["ref"]);

        if($erro !== ""){
            $_SESSION["form_veiculo"] = $_POST;
            voltar("veiculos", "danger", [$erro]);
        }

        voltar("veiculos", "success", ["Veículo " . fmtPlaca($placa) . " cadastrado."]);
        break;

    /* ================= VEÍCULOS: ENTRADA ================= */
    case "veiculo_entrada":
        $veiculoId = (int)($_POST["veiculo_id"] ?? 0);
        $veiculo   = buscarVeiculo(carregarPortaria(), $veiculoId);

        if(!$veiculo){
            voltar("veiculos", "danger", ["Veículo não encontrado."]);
        }

        // o proprietário precisa estar liberado para o veículo entrar
        $dono = buscarPessoa($veiculo["proprietario_ref"], $pessoas);

        if(!$dono){
            voltar("veiculos", "danger", ["Proprietário do veículo não encontrado."]);
        }

        $avaliacao = avaliarAcesso($dono, $conv);

        if(!$avaliacao["liberado"]){
            voltar("veiculos", "danger", array_merge(["Entrada do veículo " . fmtPlaca($veiculo["placa"]) . " não liberada."], $avaliacao["motivos"]));
        }

        $erro = registrarEntradaVeiculo($veiculoId, $operador);

        if($erro !== ""){
            voltar("veiculos", "danger", [$erro]);
        }

        voltar("veiculos", "success", ["Entrada do veículo " . fmtPlaca($veiculo["placa"]) . " registrada."]);
        break;

    /* ================= VEÍCULOS: SAÍDA ================= */
    case "veiculo_saida":
        $veiculoId = (int)($_POST["veiculo_id"] ?? 0);
        $erro      = registrarSaidaVeiculo($veiculoId);

        if($erro !== ""){
            voltar("veiculos", "danger", [$erro]);
        }

        voltar("veiculos", "success", ["Saída do veículo registrada."]);
        break;

    default:
        voltar($aba, "danger", ["Ação inválida."]);
}
