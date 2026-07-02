import Requests from "../components/requests.js";
import Validate from "../components/validate.js";
import DataTables from '../components/data-tables.js';

const Id      = document.getElementById('id');
const Insert  = document.getElementById('buttonRegister');
const FormUser = document.getElementById('formUser');

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

mdRegister.addEventListener('click', () => {
    FormUser.reset();
    $('#modalRegisterUser').modal('show');
});

mdBack.addEventListener('click', () => {
    window.location.href = '/admin/gestao';
});

// ── Cadastro de usuário ──────────────────────────────────────────
async function insertUser() {
    const requests = new Requests();
    try {
        const response = await requests.setForm('formUser').post('/users/insert');
        return response;
    } catch (error) {
        return { status: false, msg: `Restrição: ${error}` };
    }
}

Insert.addEventListener('click', async () => {
    if (!FormUser.reportValidity()) return;

    const response = await insertUser();

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

    Swal.fire({
        title: 'Cadastrado!',
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