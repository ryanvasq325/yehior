import Requests from "../components/requests.js";
import Validate from "../components/validate.js";
import DataTables from '../components/data-tables.js';

const table   = DataTables.SetId('table-supplier').setRequestVariables([]).post('/fornecedor/listingdata');
const Action  = document.getElementById('action');
const Id      = document.getElementById('id');
const Insert  = document.getElementById('buttonRegister');
const FormSupplier = document.getElementById('formSupplier');

Inputmask({ mask: ['999.999.999-99', '99.999.999/9999-99'], keepStatic: true }).mask("#numeroDocumento");
Inputmask({ mask: ['99/99/9999'] }).mask("#dataRegistro");
$('#dataRegistro').flatpickr({
    enableTime: false,
    dateFormat: "d/m/Y",
    locale: "pt"
});

// ── Alterna o modal entre modo "cadastro" e modo "edição" ─────────
function setModoCadastro() {
    Action.value = 'c';
    Id.value     = '';
    document.getElementById('modalRegisterSupplierLabel').textContent = 'Cadastro de Fornecedor';
}

function setModoEdicao() {
    Action.value = 'e';
    document.getElementById('modalRegisterSupplierLabel').textContent = 'Editar Fornecedor';
}

mdRegister.addEventListener('click', () => {
    FormSupplier.reset();
    setModoCadastro();
    $('#modalRegisterSupplier').modal('show');
});

mdBack.addEventListener('click', () => {
    window.location.href = '/admin/gestao';
});

// ── Busca os dados do fornecedor e abre o modal preenchido ────────
async function EditSupplier(id) {
    const requests = new Requests();
    try {
        const response = await requests.get(`/fornecedor/detalhes/${id}`);

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

        const supplier = response.data;

        FormSupplier.reset();
        document.getElementById('nomeExibicao').value      = supplier.nome_fantasia ?? '';
        document.getElementById('nomeLegal').value         = supplier.sobrenome_razao ?? '';
        document.getElementById('numeroDocumento').value   = supplier.cpf_cnpj ?? '';
        document.getElementById('inscricaoEstadual').value = supplier.inscricao_estadual ?? '';
        document.getElementById('dataRegistro').value      = supplier.nascimento_fundacao ?? '';

        setModoEdicao();
        Id.value = supplier.id;

        $('#modalRegisterSupplier').modal('show');
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

// ── Cadastro / atualização de fornecedor ──────────────────────────
async function applyChanges() {
    const IsValid = Validate.SetForm('formSupplier').Validate();
    if (!IsValid) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: `Por favor, corrija os erros no formulário antes de salvar.`,
            timer: 3000,
            timerProgressBar: true,
        });
        return;
    }

    $('button').prop('disabled', true);

    const requests = new Requests();
    try {
        const response = (Action.value === 'e')
            ? await requests.setForm('formSupplier').post('/fornecedor/update')
            : await requests.setForm('formSupplier').post('/fornecedor/insert');

        if (!response.status) {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: response.msg || 'Ocorreu um erro ao salvar os dados do fornecedor.',
                timer: 3000,
                timerProgressBar: true,
            });
            return;
        }

        $('#modalRegisterSupplier').modal('hide');
        FormSupplier.reset();
        setModoCadastro();

        Swal.fire({
            icon: 'success',
            title: 'Sucesso',
            text: response.msg || 'Fornecedor salvo com sucesso!',
            timer: 2000,
            timerProgressBar: true,
        }).then(() => {
            table.ajax.reload();
        });
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: `Restrição: ${error.message}`,
            timer: 3000,
            timerProgressBar: true,
        });
    } finally {
        $('button, input, checkbox').prop('disabled', false);
    }
}

Insert.addEventListener('click', async () => {
    await applyChanges();
});

// ── Exclusão de fornecedor ─────────────────────────────────────────
async function deleteSupplier() {
    const requests = new Requests();
    try {
        const response = await requests.setForm('formSupplier').post('/fornecedor/delete');
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
            const response = await deleteSupplier();
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

window.ShowModal     = ShowModal;
window.EditSupplier  = EditSupplier;