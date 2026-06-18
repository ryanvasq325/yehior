import Requests from "../components/requests.js";
import Validate from "../components/validate.js";

const Action = document.getElementById('action');
const Id     = document.getElementById('id');
const Cnpj   = document.getElementById('numeroDocumento');
const Insert = document.getElementById('buttonRegister');

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
        const response = (Action.value !== 'e')
            ? await requests.setForm('formSupplier').post('/fornecedor/insert')
            : await requests.setForm('formSupplier').post('/fornecedor/update');
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
        const redirectUrl = `${baseUrl}/admin/listsuppliers/detalhes/${response.id}`;
        if (Action.value === 'e') {
            Swal.fire({
                icon: 'success',
                title: 'Sucesso',
                text: response.msg || 'Dados do fornecedor alterados com sucesso.',
                timer: 3000,
                timerProgressBar: true,
            }).then(() => {
                window.location.href = '/admin/listsuppliers';
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

Insert.addEventListener('click', async () => {
    await applyChanges();
});