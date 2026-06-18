import Requests from "../components/requests.js";
import Validate from "../components/validate.js";

const Action = document.getElementById('action');
const Id = document.getElementById('id');
const Insert = document.getElementById('buttonRegister');

mdProduct.addEventListener('click', () => {
    $('#modalProducts').modal('show');
});

const inputmaskConfig = {
    radixPoint: ",",
    inputtype: "text",
    prefix: "R$ ",
    autoGroup: true,
    groupSeparator: ".",
    rightAlign: false,
    onBeforeMask: function (value) {
        return String(value).replace(".", ",");
    },
};

Inputmask("currency", inputmaskConfig).mask("#preco_compra");

function limparInputsParaEnvio() {
    ["#preco_compra"].forEach(seletor => {
        const campo = document.querySelector(seletor);
        if (campo && campo.inputmask) {
            let valorPuro = campo.inputmask.unmaskedvalue();
            valorPuro = valorPuro.replace(",", ".");

            campo.inputmask.remove();
            campo.value = valorPuro;
        }
    });
}

function restaurarMascaras() {
    Inputmask("currency", inputmaskConfig).mask("#preco_compra");
}

async function applyChanges() {
    const IsValid = Validate.SetForm('formProduct').Validate();
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
        limparInputsParaEnvio();
        const response = (Action.value !== 'e')
            ? await requests.setForm('formProduct').post('/produto/insert')
            : await requests.setForm('formProduct').post('/produto/update');

        if (!response.status) {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: response.msg || 'Ocorreu um erro ao salvar os dados da produto.',
                timer: 3000,
                timerProgressBar: true,
            });
            restaurarMascaras();
            return;
        }

        const baseUrl = window.location.origin;
        const redirectUrl = `${baseUrl}/admin/listproducts/detalhes/${response.id}`;

        if (Action.value === 'e') {
            Swal.fire({
                icon: 'success',
                title: 'Sucesso',
                text: response.msg || 'Dados da produto alterados com sucesso.',
                timer: 3000,
                timerProgressBar: true,
            }).then(() => {
                window.location.href = '/admin/listproducts';
            });
            return;
        }

        Action.value = 'e';
        Id.value = response.id;
        window.history.pushState({}, '', redirectUrl);
        Swal.fire({
            icon: 'success',
            title: 'Sucesso',
            text: response.msg || 'produto salva com sucesso!',
            timer: 3000,
            timerProgressBar: true,
        });
    } catch (error) {
        restaurarMascaras();
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