# 💡 Yehi Or — Sistema de Reporte e Gestão de Postes

> Plataforma web colaborativa que conecta cidadãos e equipes de manutenção para resolução ágil de problemas em postes de iluminação pública.

---

## 📌 Sobre o Projeto

O **LuzAlerta** é uma aplicação web desenvolvida por uma equipe de 3 pessoas com o objetivo de modernizar a gestão da iluminação pública municipal. O sistema permite que qualquer cidadão reporte problemas em postes próximos à sua residência — como lâmpadas apagadas, fios cortados ou estruturas danificadas — diretamente pelo navegador, sem precisar ligar para nenhuma central.

Do outro lado, administradores e equipes de gestão têm acesso a um painel completo para visualizar, priorizar e acompanhar a resolução de cada ocorrência.

---

## 🎯 Objetivo

Criar uma ponte digital entre o cidadão e a gestão pública, tornando o processo de reporte e resolução de problemas em postes mais **rápido**, **transparente** e **eficiente**.

---

## 👥 Tipos de Usuário

| Perfil | Acesso | Descrição |
|---|---|---|
| 🏠 **Cidadão / Cliente** | Público ou login simples | Reporta problemas em postes, acompanha o status do seu reporte |
| 🛠️ **Administrador / Gestor** | Login restrito | Visualiza todos os reportes, atualiza status, gerencia ocorrências |

O sistema identifica o tipo de usuário no momento do login e redireciona para a interface correspondente.

---

## ✨ Funcionalidades Planejadas

### Para o Cidadão
- [x] Criar conta e fazer login
- [ ] Reportar problema em um poste (tipo do problema, endereço, foto opcional)
- [ ] Acompanhar o status do reporte em tempo real
- [ ] Receber notificação quando o problema for resolvido
- [ ] Histórico de reportes realizados

### Para o Administrador
- [ ] Painel com todos os reportes recebidos
- [ ] Filtrar por status (pendente, em andamento, resolvido)
- [ ] Atualizar o status de uma ocorrência
- [ ] Visualizar localização dos postes reportados
- [ ] Dashboard com métricas e estatísticas

---

## 🗂️ Tipos de Problema Suportados

- 💡 Lâmpada apagada
- 🔴 Luz piscando / com defeito
- ✂️ Fio cortado ou exposto
- 🏚️ Estrutura do poste danificada
- 🌑 Trecho de rua sem iluminação
- ❓ Outro (campo aberto)

---

## 🛠️ Tecnologias

**Frontend**
- HTML5
- CSS3
- Bootstrap
- JavaScript

**Backend**
- PHP

**Banco de Dados**
- PostgreSQL

**Abstração de Banco de Dados**
- Doctrine/DBAL

---

## 🗺️ Roadmap

- [ ] Definição do stack e configuração do repositório
- [ ] Modelagem do banco de dados
- [ ] Sistema de autenticação com dois perfis
- [ ] Tela de reporte para o cidadão
- [ ] Painel administrativo
- [ ] Integração com mapa
- [ ] Testes e correções
- [ ] Deploy

---

## 👨‍💻 Equipe

| Nome | Função |
|---|---|
| Allan | Tabelas e Banco |
| Athos | Frontend |
| Ryan | Backend |

---

## 📄 Licença

Este projeto foi desenvolvido para fins acadêmicos/educacionais.

---

> *"Se você viu o poste apagado, reporte. A cidade agradece."* 🌃
