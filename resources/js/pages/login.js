document.querySelector('button').addEventListener('click', () => {
  const email = document.querySelector('input[type="email"]').value.trim();
  const senha = document.querySelector('input[type="password"]').value.trim();

  if (!email || !senha) {
    alert('Por favor, preencha todos os campos.');
    return;
  }

  // Aqui futuramente será feita a requisição ao backend PHP
  console.log('Login enviado:', { email });
});