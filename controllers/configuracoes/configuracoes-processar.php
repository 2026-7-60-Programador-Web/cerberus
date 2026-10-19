<?php

session_start();
require_once __DIR__ . "/../../models/configuracoes-dados.php";

// só administrador logado
if(!isset($_SESSION["email"]) || $_SESSION["admin"] !== true){
    header("Location: ../../views/autenticacao/login.php");
    exit;
}

if($_SERVER["REQUEST_METHOD"] !== "POST"){
    header("Location: ../../views/administrador/configuracoes.php");
    exit;
}

// lê um campo do formulário sempre como texto (evita erro se alguém mandar um array)
function textoPost(string $campo): string {
    $valor = $_POST[$campo] ?? "";

    return is_string($valor) ? $valor : "";
}

// Guarda a mensagem e volta para a lista de feedbacks, mantendo os filtros que estavam na tela.
function voltar(string $tipo, string $texto): void {
    $_SESSION["msg_configuracoes"] = ["tipo" => $tipo, "textos" => [$texto]];

    $query = ["secao" => "feedback"];

    $status = textoPost("f_status");
    if(in_array($status, ["nao_lidos", "lidos"], true)){
        $query["status"] = $status;
    }

    $tipoFiltro = textoPost("f_tipo");
    if(isset(TIPOS_FEEDBACK[$tipoFiltro])){
        $query["tipo"] = $tipoFiltro;
    }

    header("Location: ../../views/administrador/configuracoes.php?" . http_build_query($query));
    exit;
}

switch(textoPost("acao")){

    /* ================= MARCAR COMO LIDO / NÃO LIDO ================= */
    case "marcar_lido":
    case "marcar_nao_lido":
        $lido = textoPost("acao") === "marcar_lido";
        $id   = (int)textoPost("id");

        if(!alterarLidoFeedback($id, $lido)){
            voltar("danger", "Feedback não encontrado.");
        }

        voltar("success", $lido ? "Feedback marcado como lido." : "Feedback marcado como não lido.");

    /* ================= MARCAR TODOS COMO LIDOS ================= */
    case "marcar_todos":
        $total = marcarTodosFeedbacksLidos();

        if($total === 0){
            voltar("info", "Não há feedbacks novos para marcar.");
        }

        voltar("success", $total . " feedback(s) marcado(s) como lido(s).");

    default:
        voltar("danger", "Ação inválida.");
}