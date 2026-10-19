<?php
// Dados do módulo CONFIGURAÇÕES (administrador): leitura dos feedbacks enviados pelos sócios.
// Usado por: views/administrador/configuracoes.php e controllers/configuracoes/configuracoes-processar.php
//
// TEMPORÁRIO: o sócio grava os feedbacks em database/feedbacks.json
// (views/associados/configuracoes.php). Aqui o administrador lê esse mesmo arquivo.
// Quando existir a tabela no banco, troque só o corpo das funções marcadas com "BANCO".

const ARQUIVO_FEEDBACKS = __DIR__ . "/../database/feedbacks.json";

// mesmos tipos que o sócio escolhe no formulário (chave => rótulo)
const TIPOS_FEEDBACK = [
    "sugestao" => "Sugestão",
    "elogio"   => "Elogio",
    "problema" => "Problema ou erro",
    "outro"    => "Outro",
];

/* ==================== ARQUIVO (BANCO) ==================== */

// Devolve a lista de feedbacks. Se o arquivo ainda não existe (ninguém enviou nada), devolve [].
function carregarFeedbacks(): array {
    // BANCO: trocar por SELECT na tabela de feedbacks
    if(!is_file(ARQUIVO_FEEDBACKS)){
        return [];
    }

    $lido = json_decode((string)file_get_contents(ARQUIVO_FEEDBACKS), true);

    if(!is_array($lido)){
        return [];
    }

    // garante todos os campos em cada item, mesmo que algum registro venha incompleto
    $padrao = [
        "id" => 0, "nome" => "", "email" => "", "tipo" => "outro",
        "mensagem" => "", "criado_em" => "", "lido" => false,
    ];

    $lista = [];

    foreach($lido as $item){
        if(is_array($item)){
            $lista[] = array_merge($padrao, $item);
        }
    }

    return $lista;
}

function salvarFeedbacks(array $lista): void {
    // BANCO: trocar por UPDATE
    file_put_contents(
        ARQUIVO_FEEDBACKS,
        json_encode(array_values($lista), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );
}

/* ==================== CONSULTAS ==================== */

function contarFeedbacksNaoLidos(array $lista): int {
    $total = 0;

    foreach($lista as $f){
        if(empty($f["lido"])){
            $total++;
        }
    }

    return $total;
}

// $status: "todos" | "nao_lidos" | "lidos"      $tipo: "" (todos) ou uma chave de TIPOS_FEEDBACK
// Devolve do mais novo para o mais antigo.
function filtrarFeedbacks(array $lista, string $status, string $tipo): array {
    $resultado = [];

    foreach($lista as $f){
        if($status === "nao_lidos" && !empty($f["lido"])){
            continue;
        }

        if($status === "lidos" && empty($f["lido"])){
            continue;
        }

        if($tipo !== "" && $f["tipo"] !== $tipo){
            continue;
        }

        $resultado[] = $f;
    }

    usort($resultado, fn($a, $b) => strcmp($b["criado_em"], $a["criado_em"]));

    return $resultado;
}

function classeBadgeTipoFeedback(string $tipo): string {
    $classes = [
        "sugestao" => "text-bg-info",
        "elogio"   => "text-bg-success",
        "problema" => "text-bg-danger",
        "outro"    => "text-bg-secondary",
    ];

    return $classes[$tipo] ?? "text-bg-secondary";
}

/* ==================== ALTERAÇÕES ==================== */

// Marca um feedback como lido/não lido. Devolve false se o id não existe.
function alterarLidoFeedback(int $id, bool $lido): bool {
    $lista = carregarFeedbacks();
    $achou = false;

    foreach($lista as &$f){
        if((int)$f["id"] === $id){
            $f["lido"] = $lido;
            $achou = true;
            break;
        }
    }
    unset($f);

    if($achou){
        salvarFeedbacks($lista);
    }

    return $achou;
}

// Marca todos como lidos. Devolve quantos estavam como "novo".
function marcarTodosFeedbacksLidos(): int {
    $lista    = carregarFeedbacks();
    $alterados = 0;

    foreach($lista as &$f){
        if(empty($f["lido"])){
            $f["lido"] = true;
            $alterados++;
        }
    }
    unset($f);

    if($alterados > 0){
        salvarFeedbacks($lista);
    }

    return $alterados;
}