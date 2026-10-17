<?php

$tituloPagina = "Cadastro dos sócios";
$menuAtivo    = "cadastro";
require __DIR__ . "/partials/cabecalho.php";

$aba = (($_GET["aba"] ?? "socios") === "convidados") ? "convidados" : "socios";

$socios     = admSocios();
$convidados = admConvidadosFormatados();

// nome do sócio que trouxe cada convidado
$nomePorId = array_column($socios, "nome", "id");

$badgeContrato = ["ativo" => "text-bg-success", "a vencer" => "text-bg-warning", "vencido" => "text-bg-danger"];
$rotuloContrato = ["ativo" => "Ativo", "a vencer" => "A vencer", "vencido" => "Vencido"];

// ficha completa de cada sócio (usada no modal)
$fichas = [];
foreach($socios as $s){
    $fichas[$s["id"]] = [
        "nome" => $s["nome"], "matricula" => $s["matricula"], "plano" => $s["plano"],
        "cpf" => admCpf($s["cpf"]), "nascimento" => admData($s["nascimento"]) . " (" . admIdade($s["nascimento"]) . " anos)",
        "email" => $s["email"], "celular" => admCelular($s["celular"]), "endereco" => $s["endereco"],
        "inicio" => admData($s["inicio"]), "fim" => admData($s["fim"]),
        "mensalidade" => admMoeda($s["mensalidade"]), "dependentes" => $s["dependentes"],
        "convidados" => array_map(fn($c) => $c["nome"], admConvidadosDoSocio($s["id"])),
    ];
}
?>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Cadastro dos sócios</h1>
    <button type="button" id="btnExportar" class="btn btn-outline-laranja btn-sm"><i class="bi bi-download me-1"></i>Exportar tabela (CSV)</button>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $aba === "socios" ? "active" : "" ?>" data-bs-toggle="tab" data-bs-target="#aba-socios" type="button" role="tab">
            <i class="bi bi-people me-1"></i>Sócios (<?= count($socios) ?>)</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $aba === "convidados" ? "active" : "" ?>" data-bs-toggle="tab" data-bs-target="#aba-convidados" type="button" role="tab">
            <i class="bi bi-person-plus me-1"></i>Convidados (<?= count($convidados) ?>)</button>
    </li>
</ul>

<div class="tab-content">

    <!-- ==================== SÓCIOS ==================== -->
    <div class="tab-pane fade <?= $aba === "socios" ? "show active" : "" ?>" id="aba-socios" role="tabpanel">
        <div class="card-painel">
            <input type="search" class="form-control campo-busca mb-3" data-filtra="#tabelaSocios" placeholder="Buscar por nome, CPF ou matrícula">

            <div class="table-responsive">
                <table class="table align-middle mb-0 tabela-cartoes" id="tabelaSocios">
                    <thead>
                        <tr><th>Matrícula</th><th>Nome</th><th>CPF</th><th>Nascimento</th><th>Contato</th><th>Plano</th><th>Início do contrato</th><th>Fim do contrato</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($socios as $s): ?>
                            <tr data-busca="<?= ae(mb_strtolower($s["nome"] . " " . $s["cpf"] . " " . $s["matricula"])) ?>">
                                <td data-label="Matrícula"><?= ae($s["matricula"]) ?></td>
                                <td data-label="Nome"><strong><?= ae($s["nome"]) ?></strong></td>
                                <td data-label="CPF"><?= admCpf($s["cpf"]) ?></td>
                                <td data-label="Nascimento"><?= admData($s["nascimento"]) ?></td>
                                <td data-label="Contato"><?= ae($s["email"]) ?><br><small><?= admCelular($s["celular"]) ?></small></td>
                                <td data-label="Plano"><?= ae($s["plano"]) ?></td>
                                <td data-label="Início do contrato"><?= admData($s["inicio"]) ?></td>
                                <td data-label="Fim do contrato"><?= admData($s["fim"]) ?> <span class="badge <?= $badgeContrato[$s["contrato"]] ?>"><?= $rotuloContrato[$s["contrato"]] ?></span></td>
                                <td class="text-end celula-acao">
                                    <button type="button" class="btn btn-sm btn-outline-laranja btn-acao-tabela" data-bs-toggle="modal"
                                            data-bs-target="#modalFicha" data-id="<?= $s["id"] ?>">Ficha</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ==================== CONVIDADOS ==================== -->
    <div class="tab-pane fade <?= $aba === "convidados" ? "show active" : "" ?>" id="aba-convidados" role="tabpanel">
        <div class="card-painel">
            <input type="search" class="form-control campo-busca mb-3" data-filtra="#tabelaConvidados" placeholder="Buscar por nome, CPF ou sócio">

            <div class="table-responsive">
                <table class="table align-middle mb-0 tabela-cartoes" id="tabelaConvidados">
                    <thead>
                        <tr><th>Nome</th><th>CPF</th><th>Nascimento</th><th>Celular</th><th>Responsável (menores)</th><th>Convidado por</th><th>Data da visita</th><th>Situação</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($convidados as $c): ?>
                            <?php $sNome = $nomePorId[$c["socio_id"]] ?? "—"; ?>
                            <tr data-busca="<?= ae(mb_strtolower($c["nome"] . " " . $c["cpf"] . " " . $sNome)) ?>">
                                <td data-label="Nome"><strong><?= ae($c["nome"]) ?></strong></td>
                                <td data-label="CPF"><?= admCpf($c["cpf"]) ?></td>
                                <td data-label="Nascimento"><?= admData($c["nascimento"]) ?> (<?= admIdade($c["nascimento"]) ?> anos)</td>
                                <td data-label="Celular"><?= admCelular($c["celular"]) ?></td>
                                <td data-label="Responsável">
                                    <?php if($c["responsavel"]): ?>
                                        <?= ae($c["responsavel"]["nome"]) ?><br><small><?= admCelular($c["responsavel"]["celular"]) ?> · <?= admCpf($c["responsavel"]["cpf"]) ?></small>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td data-label="Convidado por"><?= ae($sNome) ?></td>
                                <td data-label="Data da visita"><?= admData($c["visita"]) ?></td>
                                <td data-label="Situação"><span class="badge <?= $c["status"] === "Aprovado" ? "text-bg-success" : "text-bg-warning" ?>"><?= ae($c["status"]) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODAL: FICHA DO SÓCIO ==================== -->
<div class="modal fade" id="modalFicha" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="fichaNome"></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0" id="fichaDados"></dl>
            </div>
        </div>
    </div>
</div>

<?php
$scriptsExtras = '
<script>
    const fichas = ' . json_encode($fichas, JSON_UNESCAPED_UNICODE) . ';
    const campos = [
        ["matricula", "Matrícula"], ["plano", "Plano"], ["cpf", "CPF"], ["nascimento", "Nascimento"],
        ["email", "E-mail"], ["celular", "Celular"], ["endereco", "Endereço"],
        ["inicio", "Início do contrato"], ["fim", "Fim do contrato"], ["mensalidade", "Mensalidade"],
        ["dependentes", "Dependentes"], ["convidados", "Convidados"]
    ];

    // ficha completa do sócio
    document.getElementById("modalFicha").addEventListener("show.bs.modal", (ev) => {
        const f = fichas[ev.relatedTarget.dataset.id];
        document.getElementById("fichaNome").textContent = f.nome;
        const dl = document.getElementById("fichaDados");
        dl.replaceChildren();
        campos.forEach(([chave, rotulo]) => {
            let valor = f[chave];
            if (Array.isArray(valor)) valor = valor.length ? valor.join(", ") : "Nenhum";
            const dt = document.createElement("dt"); dt.className = "col-sm-5"; dt.textContent = rotulo;
            const dd = document.createElement("dd"); dd.className = "col-sm-7"; dd.textContent = valor;
            dl.append(dt, dd);
        });
    });

    // busca nas tabelas
    document.querySelectorAll("[data-filtra]").forEach(campo => {
        campo.addEventListener("input", () => {
            const termo = campo.value.toLowerCase().trim();
            document.querySelectorAll(campo.dataset.filtra + " tbody tr").forEach(tr => {
                tr.style.display = tr.dataset.busca.includes(termo) ? "" : "none";
            });
        });
    });

    // exporta a tabela da aba aberta para CSV (abre no Excel)
    document.getElementById("btnExportar").addEventListener("click", () => {
        const tabela = document.querySelector(".tab-pane.active table");
        const linhas = [...tabela.querySelectorAll("tr")].filter(tr => tr.style.display !== "none").map(tr =>
            [...tr.children].filter(c => !c.classList.contains("celula-acao") && !(c.tagName === "TH" && c.textContent.trim() === ""))
                .map(c => "\"" + c.textContent.replace(/\s+/g, " ").trim().replace(/"/g, "\"\"") + "\"").join(";"));
        const blob = new Blob(["\ufeff" + linhas.join("\n")], { type: "text/csv;charset=utf-8" });
        const a = document.createElement("a");
        a.href = URL.createObjectURL(blob);
        a.download = tabela.id + ".csv";
        a.click();
        URL.revokeObjectURL(a.href);
    });
</script>';
require __DIR__ . "/partials/rodape.php";
