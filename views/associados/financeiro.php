<?php
$abertas = [
    ["referencia" => "Outubro/2026",  "vencimento" => "10/10/2026", "valor" => "R$ 350,00", "pix" => "00020126360014BR.GOV.BCB.PIX0114+5585999990000"],
];

$pagas = [
    ["referencia" => "Setembro/2026", "pagamento" => "08/09/2026", "valor" => "R$ 350,00"],
    ["referencia" => "Agosto/2026",   "pagamento" => "09/08/2026", "valor" => "R$ 350,00"],
];
?>

<h1 class="h4 mb-3">Financeiro</h1>

<div class="card-painel mb-3">
    <h2 class="h6 mb-3">Mensalidades em aberto</h2>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Referência</th><th>Vencimento</th><th>Valor</th><th class="text-end">Ações</th></tr></thead>
            <tbody>
                <?php foreach($abertas as $m): ?>
                    <tr>
                        <td><?= e($m["referencia"]); ?></td>
                        <td><?= e($m["vencimento"]); ?></td>
                        <td><?= e($m["valor"]); ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-laranja" data-bs-toggle="modal" data-bs-target="#modalPix"
                                    data-codigo="<?= e($m['pix']); ?>">PIX</button>
                            <a href="#" class="btn btn-sm btn-outline-laranja">Boleto</a>
                            <a href="#" class="btn btn-sm btn-outline-laranja">2ª via</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card-painel">
    <h2 class="h6 mb-3">Mensalidades pagas</h2>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Referência</th><th>Pago em</th><th>Valor</th><th class="text-end">Comprovante</th></tr></thead>
            <tbody>
                <?php foreach($pagas as $m): ?>
                    <tr>
                        <td><?= e($m["referencia"]); ?></td>
                        <td><?= e($m["pagamento"]); ?></td>
                        <td><?= e($m["valor"]); ?></td>
                        <td class="text-end"><a href="#" class="btn btn-sm btn-outline-laranja">2ª via</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL PIX (um só, reaproveitado por todos os botões) -->
<div class="modal fade" id="modalPix" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5">Pagar com PIX</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="small">Copie o código abaixo e cole no app do seu banco (PIX copia e cola):</p>
                <textarea id="codigoPix" class="form-control mb-3" rows="3" readonly></textarea>
                <button id="btnCopiarPix" type="button" class="btn btn-laranja w-100">Copiar código</button>
            </div>
        </div>
    </div>
</div>

<script>
    const modalPix = document.getElementById('modalPix');

    // quando o modal abre, pega o código do botão que foi clicado
    modalPix.addEventListener('show.bs.modal', (evento) => {
        const codigo = evento.relatedTarget.getAttribute('data-codigo');
        document.getElementById('codigoPix').value = codigo;
    });

    document.getElementById('btnCopiarPix').addEventListener('click', (evento) => {
        navigator.clipboard.writeText(document.getElementById('codigoPix').value);
        evento.target.textContent = 'Copiado!';
    });
</script>