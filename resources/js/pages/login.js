import Swal from "sweetalert2";
import Validate from "../components/validate.js";
import Request from "../components/requests.js";
Inputmask('999.999.999-99').mask('#cpf');
Inputmask('(99) 9999-9999').mask('#telefone');

const mdPreRegister     = document.getElementById('mdPreRegister');
const buttonPreRegister = document.getElementById('buttonPreRegister');
const buttonLogin       = document.getElementById('buttonLogin');

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
            window.location.href = '/home';
        });

    } catch (error) {
        let texto = 'Ocorreu um erro ao autenticar. Tente novamente!';

        if (error.message?.includes('403')) {
            texto = 'Verifique seu login e senha ou seu acesso ainda não foi liberado pelo administrador.';
        } else if (error.message?.includes('429')) {
            texto = 'Muitas tentativas. Tente novamente em alguns minutos.';
        } else if (error.message?.includes('500')) {
            texto = 'Erro interno. Tente novamente mais tarde.';
        }

        Swal.fire({
            icon: 'error',
            title: 'Ops...',
            text: texto,
            timer: 2500,
            timerProgressBar: true
        });
    } finally {
        buttonLogin.disabled    = false;
        buttonLogin.textContent = originalText;
    }
});

buttonPreRegister.addEventListener('click', async () => {
    const validou = Validate.SetForm('form').Validate();
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

        const response = await requests.setForm('form').post('/authentication/preregister');

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
        const texto = error.data?.msg
            || error.message
            || 'Ocorreu um erro ao cadastrar o usuário!';

        Swal.fire({
            icon: 'error',
            title: 'Ops...',
            text: texto,
            timer: 2500,
            timerProgressBar: true
        });
    } finally {
        buttonPreRegister.disabled    = false;
        buttonPreRegister.textContent = originalText;
    }
});