/*Alterna entre tema claro e escuro, salva a preferência no localStorage
e atualiza o ícone do botão (lua = tema atual é escuro, sol = tema atual é claro).
O tema inicial já foi aplicado antes disso por public/theme-init.js (evita flash).*/

document.addEventListener('DOMContentLoaded', () => {
    const btn  = document.getElementById('btn-toggle-theme');
    const icon = document.getElementById('theme-icon');
    const html = document.documentElement;

    if (!btn || !icon) return;

    function atualizarIcone() {
        const temaAtual = html.getAttribute('data-bs-theme');
        icon.classList.toggle('fa-moon', temaAtual === 'dark');
        icon.classList.toggle('fa-sun',  temaAtual === 'light');
    }

    atualizarIcone();

    btn.addEventListener('click', () => {
        const novoTema = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-bs-theme', novoTema);
        localStorage.setItem('yehior_theme', novoTema);
        atualizarIcone();
    });
});