(() => {
    const body = document.body;
    const btnTema = document.getElementById('btnTema');
    const btnMenu = document.getElementById('btnMenu');
    const overlay = document.getElementById('sidebarOverlay');
    const telaPequena = window.matchMedia('(max-width: 767.98px)');

    /* ---------- tema claro/escuro ---------- */
    const temaSalvo = localStorage.getItem('tema') || 'light';
    document.documentElement.setAttribute('data-theme', temaSalvo);
    document.documentElement.setAttribute('data-bs-theme', temaSalvo);

    const iconeTema = (tema) => (tema === 'dark' ? '☀️' : '🌙');

    if (btnTema) {
        btnTema.textContent = iconeTema(temaSalvo);

        btnTema.addEventListener('click', () => {
            const novoTema = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', novoTema);
            document.documentElement.setAttribute('data-bs-theme', novoTema);
            localStorage.setItem('tema', novoTema);
            btnTema.textContent = iconeTema(novoTema);
        });
    }

    /* ---------- menu lateral recolhível ---------- */
    if (!btnMenu) return;

    // lembra se o menu estava recolhido (só vale para telas grandes)
    if (localStorage.getItem('sidebarRecolhida') === 'true') {
        body.classList.add('sidebar-recolhida');
    }

    const atualizarAria = () => {
        const aberto = telaPequena.matches
            ? body.classList.contains('sidebar-aberta')
            : !body.classList.contains('sidebar-recolhida');
        btnMenu.setAttribute('aria-expanded', aberto);
    };

    const fecharMenuMobile = () => {
        body.classList.remove('sidebar-aberta');
        atualizarAria();
    };

    btnMenu.addEventListener('click', () => {
        if (telaPequena.matches) {
            body.classList.toggle('sidebar-aberta');
        } else {
            const recolhida = body.classList.toggle('sidebar-recolhida');
            localStorage.setItem('sidebarRecolhida', recolhida);
        }
        atualizarAria();
    });

    // no celular: fecha ao clicar fora, apertar Esc ou escolher um item
    overlay.addEventListener('click', fecharMenuMobile);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') fecharMenuMobile();
    });
    document.querySelectorAll('.sidebar-link').forEach((link) => {
        link.addEventListener('click', () => {
            if (telaPequena.matches) fecharMenuMobile();
        });
    });

    // se a janela mudar de tamanho, garante que não fique nenhum estado "preso"
    telaPequena.addEventListener('change', fecharMenuMobile);

    atualizarAria();
})();