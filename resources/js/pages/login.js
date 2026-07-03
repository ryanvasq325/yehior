import Swal from "sweetalert2";
import Validate from "../components/validate.js";
import Request from "../components/requests.js";

Inputmask('999.999.999-99').mask('#cpf');
Inputmask('(99) 9999-9999').mask('#telefone');

const mdPreRegister     = document.getElementById('mdPreRegister');
const buttonPreRegister = document.getElementById('buttonPreRegister');
const buttonLogin       = document.getElementById('buttonLogin');

// Ajusta largura do botão Google para bater com o botão Entrar
document.addEventListener('DOMContentLoaded', () => {
    const googleBtn = document.querySelector('.g_id_signin');
    if (googleBtn && buttonLogin) {
        googleBtn.setAttribute('data-width', buttonLogin.offsetWidth);
    }
});

// =============================================================================
// FEEDBACK DO LOGIN VIA GOOGLE
// O backend redireciona para /login?google=pendente ou /login?google=erro
// após tentativas de login com Google. Aqui a gente lê esse parâmetro e
// mostra a mensagem correspondente assim que a página carrega.
// =============================================================================
document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const status = params.get('google');

    if (!status) return;

    const mensagens = {
        pendente: {
            icon: 'info',
            title: 'Quase lá!',
            text: 'Cadastro recebido. Espere o administrador liberar seu acesso para poder entrar.',
        },
        erro: {
            icon: 'error',
            title: 'Ops...',
            text: 'Não foi possível concluir o login com o Google. Tente novamente.',
        },
    };

    const msg = mensagens[status];
    if (msg) {
        Swal.fire({ ...msg, confirmButtonColor: '#F5C518' });
    }

    // Remove o parâmetro da URL para não repetir a mensagem em um F5
    params.delete('google');
    const novaUrl = window.location.pathname + (params.toString() ? `?${params}` : '');
    window.history.replaceState({}, '', novaUrl);
});

mdPreRegister.addEventListener('click', () => {
    $('#modalPreRegisterUser').modal('show');
});

buttonLogin.addEventListener('click', async () => {
    const validou = Validate.SetForm('form').Validate();
    if (!validou) {
        Swal.fire({
            icon: 'error',
            title: 'Ops...',
            text: 'Preencha seu login e senha!',
            timer: 2500,
            timerProgressBar: true
        });
        return;
    }

    const requests     = new Request();
    const originalText = buttonLogin.textContent;

    try {
        buttonLogin.textContent = 'Autenticando, por favor aguarde...';
        buttonLogin.disabled    = true;

        const response = await requests.setForm('form').post('/authentication/authenticate');

        if (!response.status) {
            Swal.fire({
                icon: 'error',
                title: 'Ops...',
                text: response.msg,
                timer: 2500,
                timerProgressBar: true
            });
            return;
        }

        Swal.fire({
            icon: 'success',
            title: 'Sucesso!',
            text: response.msg,
            timer: 1500,
            timerProgressBar: true
        }).then(() => {
            window.location.href = response.redirect;
        });

    } catch (error) {
        let texto = 'Ocorreu um erro ao autenticar. Tente novamente!';

        if (error.message?.includes('403'))      texto = 'Verifique seu login e senha ou seu acesso ainda não foi liberado pelo administrador.';
        else if (error.message?.includes('429')) texto = 'Muitas tentativas. Tente novamente em alguns minutos.';
        else if (error.message?.includes('500')) texto = 'Erro interno. Tente novamente mais tarde.';

        Swal.fire({ icon: 'error', title: 'Ops...', text: texto, timer: 2500, timerProgressBar: true });
    } finally {
        buttonLogin.disabled    = false;
        buttonLogin.textContent = originalText;
    }
});

buttonPreRegister.addEventListener('click', async () => {
    const validou = Validate.SetForm('formPreRegister').Validate();
    if (!validou) {
        Swal.fire({
            icon: 'error',
            title: 'Ops...',
            text: 'Preencha os campos corretamente!',
            timer: 2500,
            timerProgressBar: true
        });
        return;
    }

    const requests     = new Request();
    const originalText = buttonPreRegister.textContent;

    try {
        buttonPreRegister.textContent = 'Cadastrando, por favor aguarde...';
        buttonPreRegister.disabled    = true;

        const response = await requests.setForm('formPreRegister').post('/authentication/preregister');

        if (!response.status) {
            Swal.fire({
                icon: 'error',
                title: 'Ops...',
                text: response.msg,
                timer: 2500,
                timerProgressBar: true
            });
            return;
        }

        Swal.fire({
            icon: 'success',
            title: 'Sucesso!',
            text: response.msg,
            timer: 2500,
            timerProgressBar: true
        }).then(() => {
            $('#modalPreRegisterUser').modal('hide');
        });

    } catch (error) {
        const texto = error.data?.msg || error.message || 'Ocorreu um erro ao cadastrar o usuário!';
        Swal.fire({ icon: 'error', title: 'Ops...', text: texto, timer: 2500, timerProgressBar: true });
    } finally {
        buttonPreRegister.disabled    = false;
        buttonPreRegister.textContent = originalText;
    }
});