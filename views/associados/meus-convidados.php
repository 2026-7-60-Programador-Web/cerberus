<?php
// Aba "Meus convidados" — é carregada dentro de painel.php (que já faz o login e define e() e $nome).

date_default_timezone_set("America/Fortaleza");

const VALIDADE_CODIGO = "+1 day";   // validade do código: 1 dia
const CHAVE_SESSAO    = "codigos_convidados";

// TEMPORÁRIO: os códigos ficam na sessão. Quando a tabela existir no banco,
// troque só as duas partes marcadas com "BANCO" abaixo.
$codigos = $_SESSION[CHAVE_SESSAO] ?? [];   // BANCO: SELECT dos códigos do sócio
$novo    = null;
$erro    = "";

$novo = $_SESSION["codigo_novo"] ?? null;
unset($_SESSION["codigo_novo"]);

// ---------- gerar código ----------
if($_SERVER["REQUEST_METHOD"] === "POST"){
    $convidado = trim($_POST["convidado"] ?? "");

    if(mb_strlen($convidado) < 3){
        $erro = "Informe o nome do convidado.";
    } else {
        // sem 0/O/1/I para não confundir na portaria
        $letras = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
        do {
            $codigo = "";
            for($i = 0; $i < 6; $i++){
                $codigo .= $letras[random_int(0, strlen($letras) - 1)];
            }
        } while(in_array($codigo, array_column($codigos, "codigo"), true));

        $novo = [
            "convidado" => mb_substr($convidado, 0, 100),
            "codigo"    => $codigo,
            "criado_em" => date("Y-m-d H:i:s"),
            "validade"  => date("Y-m-d H:i:s", strtotime(VALIDADE_CODIGO)),
        ];

        $codigos[] = $novo;                       // BANCO: INSERT do novo código
        $_SESSION[CHAVE_SESSAO] = $codigos;

        // redireciona para não gerar outro código se a página for atualizada
        $_SESSION["codigo_novo"] = $novo;
        header("Location: ?pagina=meus-convidados");
        exit;
    
    }
}

// histórico: mais recentes primeiro
$historico = array_reverse($codigos);
$agora     = date("Y-m-d H:i:s");
?>

<h1 class="h4 mb-4">Meus convidados</h1>

<div class="row g-3 mb-3">

    <!-- GERAR CÓDIGO -->
    <div class="col-12 col-lg-6">
        <div class="card-painel h-100">
            <h2 class="h6 mb-3">Gerar código para convidado</h2>

            <?php if($erro): ?>
                <div class="alert alert-danger py-2"><?= e($erro); ?></div>
            <?php endif; ?>

            <form method="POST" action="?pagina=meus-convidados">
                <label for="convidado" class="form-label">Nome do convidado</label>
                <input type="text" id="convidado" name="convidado" class="form-control form-control-lg mb-3"
                       maxlength="100" autocomplete="off" required>

                <button type="submit" class="btn btn-laranja btn-lg w-100">
                    <i class="bi bi-qr-code me-1"></i> Gerar código
                </button>
                <div class="form-text mt-2">O código vale por 1 dia a partir da geração.</div>
            </form>
        </div>
    </div>

    <!-- CÓDIGO GERADO -->
    <?php if($novo): ?>
        <div class="col-12 col-lg-6">
            <div class="card-painel h-100 text-center">
                <h2 class="h6 mb-1">Código de <?= e($novo["convidado"]); ?></h2>
                <div class="display-5 fw-bold text-laranja my-2" id="codigoGerado"
                     style="letter-spacing:.15em; color: var(--laranja);"><?= e($novo["codigo"]); ?></div>

                <div class="carteirinha-qr d-inline-block my-2"><div id="qrcode"></div></div>

                <div class="small mb-3">
                    Válido até <strong><?= date("d/m/Y H:i", strtotime($novo["validade"])); ?></strong>
                </div>

                <button type="button" id="btnCopiar" class="btn btn-outline-laranja w-100">
                    <i class="bi bi-clipboard me-1"></i> Copiar código
                </button>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- HISTÓRICO -->
<div class="card-painel">
    <h2 class="h6 mb-3">Histórico de convidados</h2>

    <?php if(empty($historico)): ?>
        <p class="text-secondary mb-0">Você ainda não gerou nenhum código.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Convidado</th>
                        <th>Código</th>
                        <th>Gerado em</th>
                        <th>Válido até</th>
                        <th>Situação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($historico as $c): ?>
                        <?php $valido = $c["validade"] >= $agora; ?>
                        <tr>
                            <td><?= e($c["convidado"]); ?></td>
                            <td><code class="fs-6"><?= e($c["codigo"]); ?></code></td>
                            <td><?= date("d/m/Y H:i", strtotime($c["criado_em"])); ?></td>
                            <td><?= date("d/m/Y H:i", strtotime($c["validade"])); ?></td>
                            <td>
                                <span class="badge <?= $valido ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                                    <?= $valido ? 'Válido' : 'Expirado'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if($novo): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    // QR Code com o código do convidado
    new QRCode(document.getElementById('qrcode'), {
        text: <?= json_encode("CERBERUS|CONVIDADO|" . $novo["codigo"]); ?>,
        width: 180,
        height: 180
    });

    document.getElementById('btnCopiar').addEventListener('click', (evento) => {
        navigator.clipboard.writeText(document.getElementById('codigoGerado').textContent);
        evento.currentTarget.textContent = 'Copiado!';
    });

    // evita gerar outro código se a pessoa atualizar a página
    history.replaceState(null, '', '?pagina=meus-convidados');
</script>
<?php endif; ?>