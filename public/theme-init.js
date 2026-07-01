
/*Aplica o tema salvo (claro/escuro) ANTES do primeiro paint da página,
para evitar o "flash" de trocar de tema na frente do usuário.
Precisa ser um <script> comum (sem type="module", sem defer/async) carregado
bem no topo do <head>, ANTES do CSS. Por isso fica fora do Vite: scripts do
tipo "module" gerados pelo bundler são sempre adiados pelo navegador, o que
reintroduziria o flash que este arquivo existe para evitar.*/
(function () {
    var temaSalvo = localStorage.getItem('yehior_theme');
    var tema = temaSalvo === 'light' ? 'light' : 'dark'; // padrão: dark
    document.documentElement.setAttribute('data-bs-theme', tema);
})();