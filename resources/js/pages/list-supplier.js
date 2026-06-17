
import FindCompany from "../components/find-company.js";
import Requests from "../components/requests.js";
import Validate from "../components/validate.js";

const Action = document.getElementById('action');
const Id     = document.getElementById('id');
const Cnpj   = document.getElementById('numeroDocumento');
const Insert = document.getElementById('insert');

mdRegister.addEventListener('click', () => {
    $('#modalRegisterSupplier').modal('show');
});

Inputmask({ mask: ['999.999.999-99', '99.999.999/9999-99'], keepStatic: true }).mask("#numeroDocumento");
Inputmask({ mask: ['99/99/9999'] }).mask("#dataRegistro");
$('#dataRegistro').flatpickr({
    enableTime: false,
    dateFormat: "d/m/Y",
    locale: "pt"
});

const findCompany = new FindCompany({ cnpjField: 'numeroDocumento', cnaeValue: 'cnae', cnaeSearch: 'codigoAtividadeEconomica' });
if (document.getElementById('codigoAtividadeEconomica')) {
    findCompany.FindCompanyCnae();
}

async function applyChanges() {
    $('button').prop('disabled', true);
    const IsValid = Validate.SetForm('form').Validate();
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
    const requests = new Requests();
    try {
        const response = (Action.value !== 'e')
            ? await requests.setForm('form').post('/fornecedor/insert')
            : await requests.setForm('form').post('/fornecedor/update');
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
        const baseUrl = window.location.origin;
        const redirectUrl = `${baseUrl}/fornecedor/detalhes/${response.id}`;
        if (Action.value === 'e') {
            Swal.fire({
                icon: 'success',
                title: 'Sucesso',
                text: response.msg || 'Dados do fornecedor alterados com sucesso.',
                timer: 3000,
                timerProgressBar: true,
            }).then(() => {
                window.location.href = '/fornecedor/lista';
            });
            return;
        }
        Action.value = 'e';
        Id.value = response.id;
        window.history.pushState({}, '', redirectUrl);
        Swal.fire({
            icon: 'success',
            title: 'Sucesso',
            text: response.msg || 'Fornecedor salvo com sucesso!',
            timer: 3000,
            timerProgressBar: true,
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

Cnpj.addEventListener('blur', async () => {
    const cleaned = Cnpj.value.replace(/\D/g, '');
    if (Cnpj.value.trim() === '' || cleaned.length < 14) {
        return;
    }
    await findCompany.FindCompanyData();
});

Insert.addEventListener('click', async () => {
    await applyChanges();
});