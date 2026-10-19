<?php

session_start();

if(!isset($_SESSION["email"]) || $_SESSION["admin"] !== true){
    header("Location: ../autenticacao/login.php");
    exit;
}

require_once __DIR__ . "/../../models/configuracoes-dados.php";
// o manual cita os limites reais do sistema (LIMITE_CONVIDADOS_POR_SOCIO e HORAS_ALERTA_PERMANENCIA)
require_once __DIR__ . "/../../models/portaria-dados.php";

$feedbacks = carregarFeedbacks();
$naoLidos  = contarFeedbacksNaoLidos($feedbacks);

$mensagem = $_SESSION["msg_configuracoes"] ?? null;
unset($_SESSION["msg_configuracoes"]);

function e($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

function formatarDataHora(string $data): string {
    return $data !== "" ? date("d/m/Y H:i", strtotime($data)) : "—";
}

// lê um parâmetro da URL sempre como texto (evita erro se vier ?tipo[]=x)
function parametroGet(string $nome): string {
    $valor = $_GET[$nome] ?? "";

    return is_string($valor) ? $valor : "";
}

// opções da tela inicial (mesmo formato da aba Configurações do sócio)
$opcoes = [
    "feedback" => ["titulo" => "Feedbacks",  "icone" => "bi-chat-left-text",  "texto" => "Veja as sugestões, elogios e problemas enviados pelos sócios."],
    "suporte"  => ["titulo" => "Suporte",    "icone" => "bi-question-circle", "texto" => "Manual rápido com as dúvidas mais comuns da administração."],
];

// manual do administrador: [pergunta, resposta]
$manual = [
    ["Como autorizar o convite de um convidado",
     "Convites novos ficam como \"Aguardando autorização\". Você pode aprová-los ou bloqueá-los na aba Visitantes da Portaria ou na aba Convites e visitas do menu Convidados."],
    ["Por que não consigo emitir mais um convite para um sócio",
     "Cada sócio pode ter até " . LIMITE_CONVIDADOS_POR_SOCIO . " convidados ativos (pendentes ou aprovados). Bloqueie um convite antigo do sócio para liberar uma vaga."],
    ["A portaria barrou um sócio. Por quê?",
     "No Painel da Portaria, busque a pessoa e veja os motivos na faixa colorida. Amarelo (Atenção) indica pendência, como carteirinha vencida ou mensalidade em aberto. Vermelho (Bloqueado) indica sócio suspenso ou convite bloqueado."],
    ["Como registrar a entrada de um visitante",
     "Primeiro o convite precisa estar aprovado. Depois, na aba Visitantes da Portaria, libere a entrada dele, ou busque pelo nome ou CPF no Painel."],
    ["Esqueci de registrar a saída de alguém",
     "No Painel da Portaria, busque a pessoa e registre a saída. O sistema também avisa quando alguém passa de " . HORAS_ALERTA_PERMANENCIA . " horas dentro do clube."],
    ["O QR Code aparece como \"Carteirinha inválida\"",
     "Esse QR não corresponde a nenhum sócio. Busque pelo nome ou pela matrícula e peça ao sócio para atualizar a carteirinha no aplicativo."],
];

// qual parte está aberta? (vazio = tela inicial)
$secao = parametroGet("secao");
if(!isset($opcoes[$secao])){
    $secao = "";
}

// filtros da lista de feedbacks
$filtroStatus = parametroGet("status");
if(!in_array($filtroStatus, ["nao_lidos", "lidos"], true)){
    $filtroStatus = "todos";
}

$filtroTipo = parametroGet("tipo");
if(!isset(TIPOS_FEEDBACK[$filtroTipo])){
    $filtroTipo = "";
}

$lista = filtrarFeedbacks($feedbacks, $filtroStatus, $filtroTipo);

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações — Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="painel-layout">

    <?php $menuAtivo = "configuracoes"; include __DIR__ . "/partials/menu.php"; ?>

    <div id="sidebarOverlay" class="sidebar-overlay"></div>

    <!-- ÁREA PRINCIPAL -->
    <div class="painel-principal">

        <header class="topbar d-flex align-items-center px-3">
            <button id="btnMenu" type="button" class="btn btn-outline-laranja btn-sm"
                    title="Recolher/expandir menu" aria-controls="sidebar" aria-expanded="true">
                <i class="bi bi-list fs-5"></i>
            </button>
            <span class="navbar-text ms-3 d-none d-sm-inline">Configurações</span>

            <div class="ms-auto">
                <button id="btnTema" type="button" class="btn btn-tema" title="Alternar tema">🌙</button>
            </div>
        </header>

        <main class="painel-conteudo">

            <?php if($mensagem): ?>
                <div class="alert alert-<?= e($mensagem["tipo"]) ?>">
                    <?php foreach($mensagem["textos"] as $texto): ?>
                        <div><?= e($texto) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if($secao === ""): ?>

                <!-- ==================== TELA INICIAL ==================== -->
                <h1 class="h4 mb-1">Configurações</h1>
                <p class="text-secondary mb-4">Acompanhe o retorno dos sócios e consulte o manual da administração.</p>

                <div class="row g-3">
                    <?php foreach($opcoes as $chave => $op): ?>
                        <div class="col-12 col-md-6">
                            <div class="card-painel h-100 d-flex align-items-center gap-3 position-relative">
                                <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                                     style="width:56px; height:56px; background: var(--laranja-suave); color: var(--laranja);">
                                    <i class="bi <?= $op["icone"] ?> fs-4"></i>
                                </div>
                                <div>
                                    <a href="?secao=<?= $chave ?>" class="stretched-link fw-semibold text-decoration-none" style="color: inherit;">
                                        <?= e($op["titulo"]) ?>
                                    </a>
                                    <?php if($chave === "feedback" && $naoLidos > 0): ?>
                                        <span class="badge text-bg-warning ms-1"><?= $naoLidos ?> novo(s)</span>
                                    <?php endif; ?>
                                    <div class="small text-secondary"><?= e($op["texto"]) ?></div>
                                </div>
                                <i class="bi bi-chevron-right ms-auto"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>

                <!-- ==================== CABEÇALHO DA SEÇÃO ==================== -->
                <div class="d-flex align-items-center gap-3 mb-4">
                    <a href="configuracoes.php" class="btn btn-outline-laranja" title="Voltar" aria-label="Voltar">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="h4 mb-0"><?= e($opcoes[$secao]["titulo"]) ?></h1>
                        <small class="text-secondary"><?= e($opcoes[$secao]["texto"]) ?></small>
                    </div>
                </div>

                <?php if($secao === "suporte"): ?>

                    <!-- ==================== SUPORTE: MANUAL ==================== -->
                    <div class="accordion" id="manual">
                        <?php foreach($manual as $i => $item): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#item<?= $i ?>">
                                        <?= e($item[0]) ?>
                                    </button>
                                </h2>
                                <div id="item<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#manual">
                                    <div class="accordion-body"><?= e($item[1]) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php else: ?>

                    <!-- ==================== FEEDBACKS RECEBIDOS ==================== -->
                    <div class="card-painel mb-3">
                        <form method="GET" class="row g-2 align-items-end">
                            <input type="hidden" name="secao" value="feedback">

                            <div class="col-12 col-sm-4">
                                <label for="status" class="form-label mb-1">Situação</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="todos"     <?= $filtroStatus === "todos"     ? "selected" : "" ?>>Todos</option>
                                    <option value="nao_lidos" <?= $filtroStatus === "nao_lidos" ? "selected" : "" ?>>Não lidos</option>
                                    <option value="lidos"     <?= $filtroStatus === "lidos"     ? "selected" : "" ?>>Lidos</option>
                                </select>
                            </div>

                            <div class="col-12 col-sm-4">
                                <label for="tipo" class="form-label mb-1">Tipo</label>
                                <select id="tipo" name="tipo" class="form-select">
                                    <option value="">Todos</option>
                                    <?php foreach(TIPOS_FEEDBACK as $valor => $rotulo): ?>
                                        <option value="<?= $valor ?>" <?= $filtroTipo === $valor ? "selected" : "" ?>><?= e($rotulo) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-sm-4 d-flex gap-2">
                                <button type="submit" class="btn btn-laranja flex-grow-1">
                                    <i class="bi bi-funnel me-1"></i> Filtrar
                                </button>
                                <a href="?secao=feedback" class="btn btn-outline-laranja" title="Limpar filtros" aria-label="Limpar filtros">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            </div>
                        </form>
                    </div>

                    <div class="d-flex align-items-center mb-3">
                        <span class="text-secondary small">
                            <?= count($lista) ?> de <?= count($feedbacks) ?> feedback(s) · <?= $naoLidos ?> não lido(s)
                        </span>

                        <?php if($naoLidos > 0): ?>
                            <form method="POST" action="../../controllers/configuracoes/configuracoes-processar.php" class="ms-auto">
                                <input type="hidden" name="acao" value="marcar_todos">
                                <input type="hidden" name="f_status" value="<?= e($filtroStatus) ?>">
                                <input type="hidden" name="f_tipo" value="<?= e($filtroTipo) ?>">
                                <button type="submit" class="btn btn-outline-laranja btn-sm">
                                    <i class="bi bi-check2-all me-1"></i> Marcar todos como lidos
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if(empty($lista)): ?>
                        <div class="card-painel text-center text-secondary">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            <?= empty($feedbacks) ? "Nenhum feedback recebido ainda." : "Nenhum feedback encontrado com esses filtros." ?>
                        </div>
                    <?php endif; ?>

                    <?php foreach($lista as $f): ?>
                        <div class="card-painel mb-3">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <span class="badge <?= classeBadgeTipoFeedback($f["tipo"]) ?>">
                                    <?= e(TIPOS_FEEDBACK[$f["tipo"]] ?? "Outro") ?>
                                </span>

                                <?php if(empty($f["lido"])): ?>
                                    <span class="badge text-bg-warning">Novo</span>
                                <?php endif; ?>

                                <small class="text-secondary ms-sm-auto">
                                    <i class="bi bi-clock me-1"></i><?= e(formatarDataHora($f["criado_em"])) ?>
                                </small>
                            </div>

                            <div class="fw-semibold"><?= e($f["nome"] !== "" ? $f["nome"] : "Sócio sem nome") ?></div>
                            <div class="small text-secondary mb-2"><?= e($f["email"] !== "" ? $f["email"] : "—") ?></div>

                            <p class="mb-3"><?= nl2br(e($f["mensagem"])) ?></p>

                            <form method="POST" action="../../controllers/configuracoes/configuracoes-processar.php">
                                <input type="hidden" name="acao" value="<?= empty($f["lido"]) ? "marcar_lido" : "marcar_nao_lido" ?>">
                                <input type="hidden" name="id" value="<?= (int)$f["id"] ?>">
                                <input type="hidden" name="f_status" value="<?= e($filtroStatus) ?>">
                                <input type="hidden" name="f_tipo" value="<?= e($filtroTipo) ?>">

                                <?php if(empty($f["lido"])): ?>
                                    <button type="submit" class="btn btn-laranja btn-sm">
                                        <i class="bi bi-check2 me-1"></i> Marcar como lido
                                    </button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-outline-laranja btn-sm">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Marcar como não lido
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    <?php endforeach; ?>

                <?php endif; ?>

            <?php endif; ?>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/script.js"></script>
</body>
</html>