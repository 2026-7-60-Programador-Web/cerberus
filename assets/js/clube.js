// Módulo de clubes: máscaras, validações do lado do cliente e pequenos comportamentos.
// IMPORTANTE: a validação de verdade é a do servidor (PHP); esta é só para ajudar o usuário.
(() => {
    const soDigitos = (v) => v.replace(/\D/g, '');

    /* ---------- máscaras ---------- */
    const mascaras = {
        cpf: (v) => soDigitos(v).slice(0, 11)
            .replace(/^(\d{3})(\d)/, '$1.$2')
            .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/, '.$1-$2'),
        cnpj: (v) => soDigitos(v).slice(0, 14)
            .replace(/^(\d{2})(\d)/, '$1.$2')
            .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/, '.$1/$2')
            .replace(/(\d{4})(\d)/, '$1-$2'),
        telefone: (v) => {
            const d = soDigitos(v).slice(0, 11);
            if (d.length <= 2) return d.replace(/^(\d+)/, '($1');
            if (d.length <= 6) return d.replace(/^(\d{2})(\d+)/, '($1) $2');
            if (d.length <= 10) return d.replace(/^(\d{2})(\d{4})(\d+)/, '($1) $2-$3');
            return d.replace(/^(\d{2})(\d{5})(\d+)/, '($1) $2-$3');
        },
        cep: (v) => soDigitos(v).slice(0, 8).replace(/^(\d{5})(\d)/, '$1-$2'),
    };

    /* ---------- regras de validação ---------- */
    const cpfValido = (valor) => {
        const c = soDigitos(valor);
        if (c.length !== 11 || /^(\d)\1{10}$/.test(c)) return false;
        for (let t = 9; t < 11; t++) {
            let soma = 0;
            for (let i = 0; i < t; i++) soma += Number(c[i]) * (t + 1 - i);
            if (Number(c[t]) !== ((10 * soma) % 11) % 10) return false;
        }
        return true;
    };

    const cnpjValido = (valor) => {
        const c = soDigitos(valor);
        if (c.length !== 14 || /^(\d)\1{13}$/.test(c)) return false;
        const pesos = {
            12: [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
            13: [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
        };
        return [12, 13].every((pos) => {
            const soma = pesos[pos].reduce((acc, p, i) => acc + Number(c[i]) * p, 0);
            const resto = soma % 11;
            return Number(c[pos]) === (resto < 2 ? 0 : 11 - resto);
        });
    };

    const telefoneValido = (valor) => {
        const d = soDigitos(valor);
        if (![10, 11].includes(d.length) || Number(d.slice(0, 2)) < 11) return false;
        return d.length === 10 || d[2] === '9';
    };

    const validadores = {
        cpf: cpfValido,
        cnpj: cnpjValido,
        telefone: telefoneValido,
        cep: (v) => soDigitos(v).length === 8,
    };

    /* ---------- aplica máscara + validação nos campos ---------- */
    document.querySelectorAll('[data-mascara]').forEach((campo) => {
        const tipo = campo.dataset.mascara;
        const mascara = mascaras[tipo];
        const validar = validadores[tipo];
        if (!mascara) return;

        const conferir = () => {
            const vazio = campo.value.trim() === '';
            campo.setCustomValidity(vazio || validar(campo.value) ? '' : 'Valor inválido');
        };

        campo.value = mascara(campo.value);
        conferir();
        campo.addEventListener('input', () => {
            campo.value = mascara(campo.value);
            conferir();
        });
    });

    // nome completo: pelo menos duas palavras
    document.querySelectorAll('input[name="nome"], input[name="admin_nome"]').forEach((campo) => {
        const conferir = () => {
            const ok = campo.value.trim() === '' || /\S+\s+\S+/.test(campo.value.trim());
            campo.setCustomValidity(ok ? '' : 'Informe nome e sobrenome');
        };
        conferir();
        campo.addEventListener('input', conferir);
    });

    // senha: 8+ caracteres com letras e números; confirmação igual
    const senha = document.getElementById('senha');
    const confirmar = document.getElementById('confirmar_senha');

    if (senha && confirmar) {
        const conferirSenha = () => {
            const v = senha.value;
            const forte = v === '' || (v.length >= 8 && /[A-Za-z]/.test(v) && /\d/.test(v));
            senha.setCustomValidity(forte ? '' : 'Senha fraca');
            confirmar.setCustomValidity(confirmar.value === v ? '' : 'As senhas não coincidem');
        };
        senha.addEventListener('input', conferirSenha);
        confirmar.addEventListener('input', conferirSenha);
    }

    // botão "olho" para mostrar/ocultar a senha
    document.querySelectorAll('[data-alternar-senha]').forEach((botao) => {
        botao.addEventListener('click', () => {
            const alvo = document.getElementById(botao.dataset.alternarSenha);
            if (!alvo) return;
            const mostrar = alvo.type === 'password';
            alvo.type = mostrar ? 'text' : 'password';
            botao.querySelector('i').className = mostrar ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });

    /* ---------- envio dos formulários ---------- */
    document.querySelectorAll('form.needs-validation').forEach((form) => {
        form.addEventListener('submit', (evento) => {
            if (!form.checkValidity()) {
                evento.preventDefault();
                evento.stopPropagation();
                form.classList.add('was-validated');
                const primeiro = form.querySelector(':invalid');
                if (primeiro) primeiro.focus();
                return;
            }

            // evita clique duplo
            const botao = form.querySelector('button[type="submit"]');
            if (botao) {
                botao.disabled = true;
                botao.dataset.original = botao.innerHTML;
                botao.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';
            }
        });
    });

    /* ---------- modal de exclusão de associado ---------- */
    const modalExcluir = document.getElementById('modalExcluir');

    if (modalExcluir) {
        modalExcluir.addEventListener('show.bs.modal', (evento) => {
            const botao = evento.relatedTarget;
            if (!botao) return;
            document.getElementById('excluirId').value = botao.dataset.id;
            document.getElementById('excluirNome').textContent = botao.dataset.nome;
        });
    }
})();
