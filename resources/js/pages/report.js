const descricao = document.getElementById('descricao');
const charCount = document.getElementById('char-count');
const cepInput = document.getElementById('cep');
const form = document.querySelector('form');


if (cepInput) {
  Inputmask('99999-999').mask(cepInput);

  cepInput.addEventListener('blur', async () => {
    const cep  = cepInput.value.replace(/\D/g, '');
    const info = document.getElementById('cep-info');

    if (cep.length !== 8) return;

    info.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Buscando CEP...';

    try {
      const res  = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
      const data = await res.json();

      if (data.erro) {
        info.innerHTML = '<span class="text-danger"><i class="fa-solid fa-circle-exclamation me-1"></i>CEP não encontrado.</span>';
      } else {
        info.innerHTML = `<i class="fa-solid fa-circle-check me-1" style="color:var(--amarelo)"></i>
          ${data.logradouro ? data.logradouro + ', ' : ''}${data.bairro}, ${data.localidade} — ${data.uf}`;
      }
    } catch {
      info.innerHTML = '<span class="text-warning"><i class="fa-solid fa-wifi me-1"></i>Não foi possível verificar o CEP.</span>';
    }
  });
}

if (descricao && charCount) {
  const update = () => {
    const len = descricao.value.length;
    charCount.textContent = `${len} / 255`;
    charCount.classList.toggle('text-danger',   len >= 240);
    charCount.classList.toggle('text-secondary', len < 240);
  };
  descricao.addEventListener('input', update);
  update();
}

if (form) {
  form.addEventListener('submit', (e) => {
    const selecionado = form.querySelector('input[name="id_tipo_problema"]:checked');
    const errorEl     = document.getElementById('problema-error');

    if (!selecionado) {
      e.preventDefault();
      errorEl.textContent = 'Selecione o tipo do problema.';
      errorEl.style.removeProperty('display');
      errorEl.closest('.mb-4').scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
      errorEl.style.display = 'none';
    }
  });
}
