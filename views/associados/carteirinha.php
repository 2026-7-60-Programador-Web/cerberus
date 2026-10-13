<?php
$socio = [
    "nome"      => $nome,
    "matricula" => "000123",
    "plano"     => "Sócio Titular",
    "validade"  => "2027-03-31",
];

$validade = new DateTime($socio["validade"]);
$hoje     = new DateTime("today");
$ativa    = $validade >= $hoje;   // true se ainda não venceu

// texto que será transformado em QR Code
$conteudoQr = "CERBERUS|" . $socio["matricula"] . "|" . $socio["validade"];
?>

<h1 class="h4 mb-4">Carteirinha digital</h1>

<div class="carteirinha">
    <img src="../../assets/img/logo-cerberus-tres-lobos.svg" alt="Logo" style="height: 50px;" class="mb-2">
    <h2 class="h5 mb-0"><?= e($socio["nome"]); ?></h2>
    <small class="text-secondary"><?= e($socio["plano"]); ?> · Matrícula <?= e($socio["matricula"]); ?></small>

    <div class="my-3">
        <div class="carteirinha-qr"><div id="qrcode"></div></div>
    </div>

    <span class="badge <?= $ativa ? 'text-bg-success' : 'text-bg-danger'; ?> mb-2">
        <?= $ativa ? 'Associação ativa' : 'Associação vencida'; ?>
    </span>
    <div class="small">Válida até <strong><?= $validade->format("d/m/Y"); ?></strong></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    new QRCode(document.getElementById('qrcode'), {
        text: <?= json_encode($conteudoQr); ?>,
        width: 180,
        height: 180
    });
</script>