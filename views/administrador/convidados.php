<?php

session_start();

if(!isset($_SESSION["email"]) || $_SESSION["admin"] !== true){
    header("Location: ../autenticacao/login.php");
    exit;
}

require_once __DIR__ . "/../../models/convidados-dados.php";

$dados  = carregarDados();
$socios = listarSocios();

$mensagem = $_SESSION["msg_convidados"] ?? null;
$antigo   = $_SESSION["form_convidado"] ?? [];
unset($_SESSION["msg_convidados"], $_SESSION["form_convidado"]);

$aba = (($_GET["aba"] ?? "cadastro") === "convites") ? "convites" : "cadastro";

function e($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

function antigo(array $antigo, string $campo): string {
    return e($antigo[$campo] ?? "");
}

function formatarData(string $data): string {
    return $data !== "" ? date("d/m/Y", strtotime($data)) : "—";   // nascimento é opcional
}

function formatarDataHora(string $data): string {
    return date("d/m/Y H:i", strtotime($data));
}

function formatarCpf(string $cpf): string {
    return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "$1.$2.$3-$4", $cpf);
}

function formatarCelular(string $celular): string {
    return $celular !== "" ? preg_replace("/(\d{2})(\d{5})(\d{4})/", "($1) $2-$3", $celular) : "—";   // celular é opcional
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convidados — Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="painel-layout">

    <?php $menuAtivo = "convidados"; include __DIR__ . "/partials/menu.php"; ?>

    <div id="sidebarOverlay" class="sidebar-overlay"></div>

    <!-- ÁREA PRINCIPAL -->
    <div class="painel-principal">

        <header class="topbar d-flex align-items-center px-3">
            <button id="btnMenu" type="button" class="btn btn-outline-laranja btn-sm"
                    title="Recolher/expandir menu" aria-controls="sidebar" aria-expanded="true">
                <i class="bi bi-list fs-5"></i>
            </button>
            <span class="navbar-text ms-3 d-none d-sm-inline">Convidados</span>

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

            <!-- ABAS -->
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $aba === 'cadastro' ? 'active' : '' ?>"
                            data-bs-toggle="tab" data-bs-target="#aba-cadastro"
                            type="button" role="tab">Cadastro de convidados</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $aba === 'convites' ? 'active' : '' ?>"
                            data-bs-toggle="tab" data-bs-target="#aba-convites"
                            type="button" role="tab">Convites e visitas</button>
                </li>
            </ul>

            <div class="tab-content">

                <!-- ==================== ABA 1: CADASTRO ==================== -->
                <div class="tab-pane fade <?= $aba === 'cadastro' ? 'show active' : '' ?>" id="aba-cadastro" role="tabpanel">
                    <div class="card-painel mb-4">
                        <h1 class="h4 mb-3">Cadastrar convidado</h1>

                        <form action="../../controllers/convidados/convidados-processar.php" method="POST">
                            <input type="hidden" name="acao" value="cadastrar">

                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="nome" class="form-label">Nome completo</label>
                                    <input type="text" class="form-control" id="nome" name="nome"
                                           value="<?= antigo($antigo, 'nome') ?>" required>
                                </div>
                                <div class="col-12 col-md-6 col-lg-3">
                                    <label for="nascimento" class="form-label">Data de nascimento <small class="text-secondary">(opcional)</small></label>
                                    <input type="date" class="form-control" id="nascimento" name="nascimento"
                                           max="<?= date('Y-m-d') ?>" value="<?= antigo($antigo, 'nascimento') ?>">
                                </div>
                                <div class="col-12 col-md-6 col-lg-3">
                                    <label for="celular" class="form-label">Celular <small class="text-secondary">(opcional)</small></label>
                                    <input type="tel" class="form-control" id="celular" name="celular"
                                           placeholder="(85) 99999-9999" maxlength="15"
                                           value="<?= antigo($antigo, 'celular') ?>">
                                </div>
                                <div class="col-12 col-md-6 col-lg-3">
                                    <label for="cpf" class="form-label">CPF</label>
                                    <input type="text" class="form-control" id="cpf" name="cpf"
                                           placeholder="000.000.000-00" maxlength="14"
                                           value="<?= antigo($antigo, 'cpf') ?>" required>
                                </div>
                            </div>

                            <!-- Dados do responsável: aparece só se o convidado for menor de 18 anos -->
                            <div id="blocoResponsavel" class="d-none mt-4">
                                <h2 class="h5">Dados do responsável</h2>
                                <p class="small mb-3">O convidado é menor de idade. Os dados de um responsável (maior de 18 anos) são obrigatórios.</p>

                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label for="resp_nome" class="form-label">Nome completo do responsável</label>
                                        <input type="text" class="form-control" id="resp_nome" name="resp_nome"
                                               value="<?= antigo($antigo, 'resp_nome') ?>">
                                    </div>
                                    <div class="col-12 col-md-6 col-lg-3">
                                        <label for="resp_nascimento" class="form-label">Data de nascimento</label>
                                        <input type="date" class="form-control" id="resp_nascimento" name="resp_nascimento"
                                               max="<?= date('Y-m-d') ?>" value="<?= antigo($antigo, 'resp_nascimento') ?>">
                                    </div>
                                    <div class="col-12 col-md-6 col-lg-3">
                                        <label for="resp_celular" class="form-label">Celular</label>
                                        <input type="tel" class="form-control" id="resp_celular" name="resp_celular"
                                               placeholder="(85) 99999-9999" maxlength="15"
                                               value="<?= antigo($antigo, 'resp_celular') ?>">
                                    </div>
                                    <div class="col-12 col-md-6 col-lg-3">
                                        <label for="resp_cpf" class="form-label">CPF</label>
                                        <input type="text" class="form-control" id="resp_cpf" name="resp_cpf"
                                               placeholder="000.000.000-00" maxlength="14"
                                               value="<?= antigo($antigo, 'resp_cpf') ?>">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-outline-laranja mt-4">Cadastrar convidado</button>
                        </form>
                    </div>

                    <!-- Lista de convidados cadastrados -->
                    <div class="card-painel">
                        <h2 class="h5 mb-3">Convidados cadastrados</h2>

                        <?php if(empty($dados["convidados"])): ?>
                            <p class="mb-0">Nenhum convidado cadastrado ainda.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Nascimento</th>
                                            <th>Celular</th>
                                            <th>CPF</th>
                                            <th>Responsável</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach(array_reverse($dados["convidados"]) as $c): ?>
                                            <tr>
                                                <td><?= e($c["nome"]) ?></td>
                                                <td><?= formatarData($c["nascimento"]) ?></td>
                                                <td><?= formatarCelular($c["celular"]) ?></td>
                                                <td><?= formatarCpf($c["cpf"]) ?></td>
                                                <td>
                                                    <?php if($c["responsavel"]): ?>
                                                        <?= e($c["responsavel"]["nome"]) ?><br>
                                                        <small><?= formatarCelular($c["responsavel"]["celular"]) ?> · <?= formatarCpf($c["responsavel"]["cpf"]) ?></small>
                                                    <?php else: ?>
                                                        —
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ==================== ABA 2: CONVITES E VISITAS ==================== -->
                <div class="tab-pane fade <?= $aba === 'convites' ? 'show active' : '' ?>" id="aba-convites" role="tabpanel">

                    <!-- Emitir convite -->
                    <div class="card-painel mb-4">
                        <h2 class="h5 mb-3">Emitir convite</h2>

                        <?php if(empty($dados["convidados"])): ?>
                            <p class="mb-0">Cadastre um convidado na aba "Cadastro de convidados" para poder emitir convites.</p>
                        <?php else: ?>
                            <form action="../../controllers/convidados/convidados-processar.php" method="POST" class="row g-3 align-items-end">
                                <input type="hidden" name="acao" value="emitir">

                                <div class="col-12 col-md-5">
                                    <label for="convidado_id" class="form-label">Convidado</label>
                                    <select class="form-select" id="convidado_id" name="convidado_id" required>
                                        <option value="">Selecione...</option>
                                        <?php foreach($dados["convidados"] as $c): ?>
                                            <option value="<?= (int)$c['id'] ?>"><?= e($c["nome"]) ?> — <?= formatarCpf($c["cpf"]) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-5">
                                    <label for="socio_id" class="form-label">Sócio responsável</label>
                                    <select class="form-select" id="socio_id" name="socio_id" required>
                                        <option value="">Selecione...</option>
                                        <?php foreach($socios as $s): ?>
                                            <?php $usados = contarConvidadosDoSocio($dados, $s["id"]); ?>
                                            <option value="<?= (int)$s['id'] ?>">
                                                <?= e($s["nome"]) ?> (<?= $usados ?>/<?= LIMITE_CONVIDADOS_POR_SOCIO ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-2">
                                    <button type="submit" class="btn btn-outline-laranja w-100">Emitir</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>

                    <!-- Limite por sócio -->
                    <div class="card-painel mb-4">
                        <h2 class="h5 mb-3">Limite de convidados por sócio (máx. <?= LIMITE_CONVIDADOS_POR_SOCIO ?>)</h2>

                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Sócio</th>
                                        <th>Convidados ativos</th>
                                        <th>Vagas restantes</th>
                                        <th>Situação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($socios as $s): ?>
                                        <?php
                                            $usados = contarConvidadosDoSocio($dados, $s["id"]);
                                            $restantes = LIMITE_CONVIDADOS_POR_SOCIO - $usados;
                                        ?>
                                        <tr>
                                            <td><?= e($s["nome"]) ?></td>
                                            <td><?= $usados ?> / <?= LIMITE_CONVIDADOS_POR_SOCIO ?></td>
                                            <td><?= max($restantes, 0) ?></td>
                                            <td>
                                                <?php if($restantes <= 0): ?>
                                                    <span class="badge text-bg-danger">Limite atingido</span>
                                                <?php else: ?>
                                                    <span class="badge text-bg-success">Disponível</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Convites emitidos (com aprovação / bloqueio) -->
                    <div class="card-painel mb-4">
                        <h2 class="h5 mb-3">Convites emitidos</h2>

                        <?php if(empty($dados["convites"])): ?>
                            <p class="mb-0">Nenhum convite emitido ainda.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Nº</th>
                                            <th>Convidado</th>
                                            <th>Sócio</th>
                                            <th>Emitido em</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach(array_reverse($dados["convites"]) as $cv): ?>
                                            <?php
                                                $convidado = buscarPorId($dados["convidados"], $cv["convidado_id"]);
                                                $socio     = buscarSocio($cv["socio_id"]);
                                            ?>
                                            <tr>
                                                <td><?= (int)$cv["id"] ?></td>
                                                <td><?= e($convidado["nome"] ?? "—") ?></td>
                                                <td><?= e($socio["nome"] ?? "—") ?></td>
                                                <td><?= formatarDataHora($cv["emitido_em"]) ?></td>
                                                <td>
                                                    <?php if($cv["status"] === "aprovado"): ?>
                                                        <span class="badge text-bg-success">Aprovado</span>
                                                    <?php elseif($cv["status"] === "bloqueado"): ?>
                                                        <span class="badge text-bg-danger">Bloqueado</span>
                                                    <?php else: ?>
                                                        <span class="badge text-bg-warning">Pendente</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <form action="../../controllers/convidados/convidados-processar.php" method="POST" class="d-flex flex-wrap gap-1">
                                                        <input type="hidden" name="convite_id" value="<?= (int)$cv['id'] ?>">

                                                        <?php if($cv["status"] !== "aprovado"): ?>
                                                            <button type="submit" name="acao" value="aprovar" class="btn btn-sm btn-outline-success">Aprovar</button>
                                                        <?php endif; ?>

                                                        <?php if($cv["status"] !== "bloqueado"): ?>
                                                            <button type="submit" name="acao" value="bloquear" class="btn btn-sm btn-outline-danger">Bloquear</button>
                                                        <?php endif; ?>

                                                        <?php if($cv["status"] === "aprovado"): ?>
                                                            <button type="submit" name="acao" value="visita" class="btn btn-sm btn-outline-secondary">Registrar visita</button>
                                                        <?php endif; ?>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Histórico de visitas -->
                    <div class="card-painel">
                        <h2 class="h5 mb-3">Histórico de visitas</h2>

                        <?php if(empty($dados["visitas"])): ?>
                            <p class="mb-0">Nenhuma visita registrada ainda.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Data e hora</th>
                                            <th>Convidado</th>
                                            <th>Sócio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach(array_reverse($dados["visitas"]) as $v): ?>
                                            <?php
                                                $convidado = buscarPorId($dados["convidados"], $v["convidado_id"]);
                                                $socio     = buscarSocio($v["socio_id"]);
                                            ?>
                                            <tr>
                                                <td><?= formatarDataHora($v["data_hora"]) ?></td>
                                                <td><?= e($convidado["nome"] ?? "—") ?></td>
                                                <td><?= e($socio["nome"] ?? "—") ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </main>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/script.js"></script>

<script>
    // Mostra os campos do responsável quando o convidado tem menos de 18 anos
    (() => {
        const nascimento = document.getElementById('nascimento');
        const bloco = document.getElementById('blocoResponsavel');
        const camposResp = bloco.querySelectorAll('input');

        const ehMenor = (valor) => {
            if (!valor) return false;
            const nasc = new Date(valor + 'T00:00:00');
            const hoje = new Date();
            let idade = hoje.getFullYear() - nasc.getFullYear();
            const fezAniversario =
                hoje.getMonth() > nasc.getMonth() ||
                (hoje.getMonth() === nasc.getMonth() && hoje.getDate() >= nasc.getDate());
            if (!fezAniversario) idade--;
            return idade < 18;
        };

        const atualizar = () => {
            const menor = ehMenor(nascimento.value);
            bloco.classList.toggle('d-none', !menor);
            camposResp.forEach((campo) => { campo.required = menor; });
        };

        nascimento.addEventListener('change', atualizar);
        atualizar();
    })();
</script>

</body>
</html>