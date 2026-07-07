import Requests from "../components/requests.js";
import Validate from "../components/validate.js";
import DataTables from '../components/data-tables.js';

const Id       = document.getElementById('id');
const Insert   = document.getElementById('buttonRegister');
const FormUser = document.getElementById('formUser');
const FormUserId    = document.getElementById('formUserId');
const ModalTitle     = document.getElementById('modalRegisterUserLabel');
const SenhaInput     = document.getElementById('senhaCadastro');
const SenhaLabel     = document.getElementById('labelSenha');

const table = DataTables.SetId('table-users').setRequestVariables([]).post('/users/listingdata');

// ── Máscaras de CPF e telefone ────────────────────────────────────
const cpfInput      = document.getElementById('cpf');
const telefoneInput = document.getElementById('telefone');

if (cpfInput) {
    Inputmask('999.999.999-99').mask(cpfInput);
}

if (telefoneInput) {
    // Aceita fixo (99) 9999-9999 e celular (99) 99999-9999 no mesmo campo
    Inputmask({
        mask: ['(99) 9999-9999', '(99) 99999-9999'],
        keepStatic: true,
    }).mask(telefoneInput);
}

// ── Alterna o modal entre modo "cadastro" e modo "edição" ─────────
function setModoCadastro() {
    FormUserId.value = '';
    ModalTitle.textContent = 'Cadastro de Usuário';
    SenhaInput.required = true;
    SenhaLabel.textContent = 'Informe sua senha *';
}

function setModoEdicao() {
    ModalTitle.textContent = 'Editar Usuário';
    // Na edição, a senha é opcional — deixar em branco mantém a atual.
    SenhaInput.required = false;
    SenhaLabel.textContent = 'Nova senha (deixe em branco para manter a atual)';
}

mdRegister.addEventListener('click', () => {
    FormUser.reset();
    setModoCadastro();
    $('#modalRegisterUser').modal('show');
});

mdBack.addEventListener('click', () => {
    window.location.href = '/admin/gestao';
});

// ── Busca os dados do usuário e abre o modal preenchido ───────────
async function EditUser(id) {
    const requests = new Requests();
    try {
        const response = await requests.get(`/users/detalhes/${id}`);

        if (!response.status) {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: response.msg,
                timer: 3000,
                timerProgressBar: true,
            });
            return;
        }

        const user = response.data;

        FormUser.reset();
        FormUserId.value = user.id;
        document.getElementById('nome').value      = user.nome ?? '';
        document.getElementById('sobrenome').value = user.sobrenome ?? '';
        document.getElementById('cpf').value       = user.cpf ?? '';
        document.getElementById('rg').value        = user.rg ?? '';
        document.getElementById('telefone').value  = user.telefone ?? '';
        document.getElementById('email').value     = user.email ?? '';
        document.getElementById('ativo').checked         = user.ativo == true;
        document.getElementById('administrador').checked = user.administrador == true;

        setModoEdicao();
        $('#modalRegisterUser').modal('show');
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: `Restrição: ${error}`,
            timer: 3000,
            timerProgressBar: true,
        });
    }
}

// ── Cadastro / atualização de usuário ─────────────────────────────
async function saveUser() {
    const requests = new Requests();
    const isEdicao = FormUserId.value !== '';
    const url      = isEdicao ? '/users/update' : '/users/insert';

    try {
        const response = await requests.setForm('formUser').post(url);
        return response;
    } catch (error) {
        return { status: false, msg: `Restrição: ${error}` };
    }
}

Insert.addEventListener('click', async () => {
    if (!FormUser.reportValidity()) return;

    const response = await saveUser();

    if (!response.status) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: response.msg,
            timer: 3000,
            timerProgressBar: true,
        });
        return;
    }

    $('#modalRegisterUser').modal('hide');
    FormUser.reset();
    setModoCadastro();

    Swal.fire({
        title: 'Sucesso!',
        text: response.msg,
        icon: 'success',
        timer: 2000,
        timerProgressBar: true,
    }).then(() => {
        table.ajax.reload();
    });
});

// ── Exclusão de usuário ───────────────────────────────────────────
async function deleteUser() {
    const requests = new Requests();
    try {
        const response = await requests.setForm('formDelete').post('/users/delete');
        return response;
    } catch (error) {
        return { status: false, msg: `Restrição: ${error}` };
    }
}

async function ShowModal(id) {
    Id.value = id;
    Swal.fire({
        title: "Atenção!",
        text: "Deseja realmente excluir este registro?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Excluir"
    }).then(async (result) => {
        if (result.isConfirmed) {
            const response = await deleteUser();
            if (!response.status) {
                Swal.fire({
                    title: "Erro!",
                    text: response.msg,
                    icon: "error",
                    timer: 3000,
                    timerProgressBar: true
                });
                return;
            }
            Swal.fire({
                title: "Removido!",
                text: "Registro excluído com sucesso.",
                icon: "success",
                timer: 2000,
                timerProgressBar: true
            }).then(async () => {
                table.ajax.reload();
            });
        }
    });
}

window.ShowModal = ShowModal;
window.EditUser  = EditUser;