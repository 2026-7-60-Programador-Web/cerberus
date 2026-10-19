<?php

$tituloPagina = "Cadastro dos sócios";
$menuAtivo    = "cadastro";
require __DIR__ . "/partials/cabecalho.php";

// mensagens e campos devolvidos pelo controller (controllers/socios/socio-cadastrar.php)
$erros   = $_SESSION["cadastro_socio_erros"]   ?? [];
$sucesso = $_SESSION["cadastro_socio_sucesso"] ?? "";
$antigos = $_SESSION["cadastro_socio_dados"]   ?? [];
unset($_SESSION["cadastro_socio_erros"], $_SESSION["cadastro_socio_sucesso"], $_SESSION["cadastro_socio_dados"]);

// valor de um campo (se deu erro, mantém o que o administrador já tinha digitado)
function valor(array $antigos, string $campo, string $padrao = ""): string {
    return ae($antigos[$campo] ?? $padrao);
}
// marca a opção escolhida de um <select>
function marcado(array $antigos, string $campo, string $opcao, string $padrao = ""): string {
    return (($antigos[$campo] ?? $padrao) === $opcao) ? "selected" : "";
}

$estadosCivis = ["Solteiro(a)", "Casado(a)", "União estável", "Separado(a)", "Divorciado(a)", "Viúvo(a)"];
$generos      = ["Feminino", "Masculino", "Outro", "Prefiro não informar"];
?>

<h1 class="h4 mb-3">Cadastro dos sócios</h1>

<?php if($sucesso !== ""): ?>
    <div class="alert alert-success" role="alert"><i class="bi bi-check-circle me-1"></i><?= ae($sucesso) ?></div>
<?php endif; ?>

<?php if(!empty($erros)): ?>
    <div class="alert alert-danger" role="alert">
        <strong><i class="bi bi-exclamation-triangle me-1"></i>Não foi possível salvar:</strong>
        <ul class="mb-0 mt-1">
            <?php foreach($erros as $erro): ?>
                <li><?= ae($erro) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="../../controllers/socios/socio-cadastrar.php" method="post" autocomplete="off">

    <!-- ========== DADOS PESSOAIS ========== -->
    <div class="card-painel mb-3">
        <h2 class="h6 mb-3"><i class="bi bi-person me-1"></i> Dados pessoais</h2>
        <div class="row g-3">
            <div class="col-12 col-md-8">
                <label for="nome" class="form-label">Nome completo *</label>
                <input type="text" class="form-control" id="nome" name="nome" maxlength="150" required value="<?= valor($antigos, "nome") ?>">
            </div>
            <div class="col-12 col-md-4">
                <label for="data_nascimento" class="form-label">Data de nascimento *</label>
                <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" max="<?= date("Y-m-d") ?>" required value="<?= valor($antigos, "data_nascimento") ?>">
            </div>
            <div class="col-12 col-md-4">
                <label for="genero" class="form-label">Gênero <small class="text-secondary">(opcional — usado em estatísticas do clube)</small></label>
                <select class="form-select" id="genero" name="genero">
                    <option value="">Não informado</option>
                    <?php foreach($generos as $g): ?>
                        <option value="<?= ae($g) ?>" <?= marcado($antigos, "genero", $g) ?>><?= ae($g) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label for="estado_civil" class="form-label">Estado civil *</label>
                <select class="form-select" id="estado_civil" name="estado_civil" required>
                    <option value="">Selecione</option>
                    <?php foreach($estadosCivis as $ec): ?>
                        <option value="<?= ae($ec) ?>" <?= marcado($antigos, "estado_civil", $ec) ?>><?= ae($ec) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label for="profissao" class="form-label">Profissão *</label>
                <input type="text" class="form-control" id="profissao" name="profissao" maxlength="100" required value="<?= valor($antigos, "profissao") ?>">
            </div>
        </div>
    </div>

    <!-- ========== DOCUMENTOS ========== -->
    <div class="card-painel mb-3">
        <h2 class="h6 mb-3"><i class="bi bi-person-vcard me-1"></i> Documentos de identificação</h2>
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <label for="cpf" class="form-label">CPF *</label>
                <input type="text" class="form-control" id="cpf" name="cpf" inputmode="numeric" maxlength="14" placeholder="000.000.000-00" required value="<?= valor($antigos, "cpf") ?>">
            </div>
            <div class="col-12 col-md-4">
                <label for="rg" class="form-label">RG *</label>
                <input type="text" class="form-control" id="rg" name="rg" maxlength="20" required value="<?= valor($antigos, "rg") ?>">
            </div>
            <div class="col-12 col-md-4">
                <label for="orgao_expedidor" class="form-label">Órgão expedidor *</label>
                <input type="text" class="form-control" id="orgao_expedidor" name="orgao_expedidor" maxlength="20" placeholder="Ex.: SSP/CE" required value="<?= valor($antigos, "orgao_expedidor") ?>">
            </div>
        </div>
    </div>

    <!-- ========== CONTATO ========== -->
    <div class="card-painel mb-3">
        <h2 class="h6 mb-3"><i class="bi bi-telephone me-1"></i> Contato</h2>
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <label for="telefone" class="form-label">Telefone *</label>
                <input type="text" class="form-control" id="telefone" name="telefone" inputmode="tel" maxlength="15" placeholder="(85) 99999-9999" required value="<?= valor($antigos, "telefone") ?>">
            </div>
            <div class="col-12 col-md-8">
                <label for="email" class="form-label">E-mail *</label>
                <input type="email" class="form-control" id="email" name="email" maxlength="150" required value="<?= valor($antigos, "email") ?>">
            </div>
            <div class="col-12 col-md-8">
                <label for="contato_emergencia_nome" class="form-label">Contato de emergência — nome *</label>
                <input type="text" class="form-control" id="contato_emergencia_nome" name="contato_emergencia_nome" maxlength="150" required value="<?= valor($antigos, "contato_emergencia_nome") ?>">
            </div>
            <div class="col-12 col-md-4">
                <label for="contato_emergencia_telefone" class="form-label">Contato de emergência — telefone *</label>
                <input type="text" class="form-control" id="contato_emergencia_telefone" name="contato_emergencia_telefone" inputmode="tel" maxlength="15" placeholder="(85) 99999-9999" required value="<?= valor($antigos, "contato_emergencia_telefone") ?>">
            </div>
        </div>
    </div>

    <!-- ========== ENDEREÇO ========== -->
    <div class="card-painel mb-3">
        <h2 class="h6 mb-3"><i class="bi bi-geo-alt me-1"></i> Endereço completo</h2>
        <div class="row g-3">
            <div class="col-12 col-md-3">
                <label for="cep" class="form-label">CEP *</label>
                <input type="text" class="form-control" id="cep" name="cep" inputmode="numeric" maxlength="9" placeholder="00000-000" required value="<?= valor($antigos, "cep") ?>">
            </div>
            <div class="col-12 col-md-6">
                <label for="logradouro" class="form-label">Logradouro (rua, avenida...) *</label>
                <input type="text" class="form-control" id="logradouro" name="logradouro" maxlength="150" required value="<?= valor($antigos, "logradouro") ?>">
            </div>
            <div class="col-6 col-md-3">
                <label for="numero" class="form-label">Número *</label>
                <input type="text" class="form-control" id="numero" name="numero" maxlength="15" required value="<?= valor($antigos, "numero") ?>">
            </div>
            <div class="col-6 col-md-4">
                <label for="complemento" class="form-label">Complemento</label>
                <input type="text" class="form-control" id="complemento" name="complemento" maxlength="80" placeholder="Apto, bloco..." value="<?= valor($antigos, "complemento") ?>">
            </div>
            <div class="col-12 col-md-3">
                <label for="bairro" class="form-label">Bairro *</label>
                <input type="text" class="form-control" id="bairro" name="bairro" maxlength="80" required value="<?= valor($antigos, "bairro") ?>">
            </div>
            <div class="col-8 col-md-3">
                <label for="cidade" class="form-label">Cidade *</label>
                <input type="text" class="form-control" id="cidade" name="cidade" maxlength="80" required value="<?= valor($antigos, "cidade", "Fortaleza") ?>">
            </div>
            <div class="col-4 col-md-2">
                <label for="uf" class="form-label">UF *</label>
                <input type="text" class="form-control text-uppercase" id="uf" name="uf" maxlength="2" required value="<?= valor($antigos, "uf", "CE") ?>">
            </div>
        </div>
    </div>

    <!-- ========== ASSOCIAÇÃO E MENSALIDADE ========== -->
    <div class="card-painel mb-3">
        <h2 class="h6 mb-3"><i class="bi bi-card-checklist me-1"></i> Associação e mensalidade</h2>
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <label for="plano" class="form-label">Plano *</label>
                <select class="form-select" id="plano" name="plano" required>
                    <option value="">Selecione</option>
                    <option value="Titular" <?= marcado($antigos, "plano", "Titular") ?>>Titular</option>
                    <option value="Família" <?= marcado($antigos, "plano", "Família") ?>>Família</option>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label for="data_inicio" class="form-label">Início do contrato *</label>
                <input type="date" class="form-control" id="data_inicio" name="data_inicio" required value="<?= valor($antigos, "data_inicio", date("Y-m-d")) ?>">
            </div>
            <div class="col-12 col-md-4">
                <label for="dia_vencimento" class="form-label">Vencimento da mensalidade *</label>
                <select class="form-select" id="dia_vencimento" name="dia_vencimento" required>
                    <option value="">Dia do mês</option>
                    <?php for($d = 1; $d <= 28; $d++): ?>
                        <option value="<?= $d ?>" <?= marcado($antigos, "dia_vencimento", (string)$d) ?>>Todo dia <?= $d ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-12">
                <label for="observacoes" class="form-label">Observações <small class="text-secondary">(restrições de saúde, necessidades especiais, etc.)</small></label>
                <textarea class="form-control" id="observacoes" name="observacoes" rows="3" maxlength="1000"><?= valor($antigos, "observacoes") ?></textarea>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
        <small class="text-secondary">Campos marcados com * são obrigatórios.</small>
        <div class="d-flex gap-2">
            <button type="submit" class="btn text-white" style="background-color: var(--laranja); border-color: var(--laranja);">
                <i class="bi bi-person-plus me-1"></i>Cadastrar sócio
            </button>
        </div>
    </div>
</form>

<?php
$scriptsExtras = '
<script>
    // máscaras simples: o administrador digita só números e o campo formata sozinho
    const mascaras = {
        cpf:      v => v.replace(/\D/g, "").slice(0, 11).replace(/(\d{3})(\d)/, "$1.$2").replace(/(\d{3})(\d)/, "$1.$2").replace(/(\d{3})(\d{1,2})$/, "$1-$2"),
        cep:      v => v.replace(/\D/g, "").slice(0, 8).replace(/(\d{5})(\d)/, "$1-$2"),
        telefone: v => v.replace(/\D/g, "").slice(0, 11).replace(/(\d{2})(\d)/, "($1) $2").replace(/(\d{5})(\d{1,4})$/, "$1-$2")
    };
    [["cpf", "cpf"], ["cep", "cep"], ["telefone", "telefone"], ["contato_emergencia_telefone", "telefone"]].forEach(([id, tipo]) => {
        const campo = document.getElementById(id);
        campo.addEventListener("input", () => { campo.value = mascaras[tipo](campo.value); });
        campo.value = mascaras[tipo](campo.value);
    });
</script>';
require __DIR__ . "/partials/rodape.php";
