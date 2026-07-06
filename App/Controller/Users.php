<?php

declare(strict_types=1);

namespace App\Controller;

final class Users extends Base
{
    public function home($request, $response)
    {
        $totalUsers = (int) \App\Database\DB::select('COUNT(*)')
            ->from('users')
            ->fetchOne();

        return $this->getTwig()
            ->render($response, $this->setView('list-users'), [
                'titulo'     => '',
                'totalUsers' => $totalUsers,
            ])
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }

    public function insert($request, $response)
    {
        $form = $request->getParsedBody();

        $nome      = trim((string) ($form['nome'] ?? ''));
        $sobrenome = trim((string) ($form['sobrenome'] ?? ''));
        $cpf       = trim((string) ($form['cpf'] ?? ''));
        $rg        = trim((string) ($form['rg'] ?? ''));
        $telefone  = trim((string) ($form['telefone'] ?? ''));
        $email     = trim((string) ($form['email'] ?? ''));
        $senha     = (string) ($form['senhaCadastro'] ?? '');

        $erros = [];
        if ($nome === '')      $erros[] = 'Informe o nome.';
        if ($sobrenome === '') $erros[] = 'Informe o sobrenome.';
        if ($cpf === '')       $erros[] = 'Informe o CPF.';
        if ($senha === '')     $erros[] = 'Informe a senha.';

        if (!empty($erros)) {
            return $this->json($response, ['status' => false, 'msg' => implode(' ', $erros)], 422);
        }

        $conn = \App\Database\DB::connection();

        try {
            $conn->beginTransaction();

            $conn->insert('users', [
                'nome'          => $nome,
                'sobrenome'     => $sobrenome,
                'cpf'           => $cpf,
                'rg'            => $rg !== '' ? $rg : null,
                'senha'         => password_hash($senha, PASSWORD_DEFAULT),
                'ativo'         => 1,
                'administrador' => 0,
                'excluido'      => 0,
            ]);

            // ⚠️ Doctrine DBAL no Postgres às vezes precisa do nome da sequence aqui,
            // ex: $conn->lastInsertId('users_id_seq'). Se der erro, testa essa variação.
            $idUser = (int) $conn->lastInsertId();

            if ($email !== '') {
                $conn->insert('contact', [
                    'id_users' => $idUser,
                    'tipo'     => 'EMAIL',
                    'contato'  => $email,
                ]);
            }

            if ($telefone !== '') {
                $conn->insert('contact', [
                    'id_users' => $idUser,
                    'tipo'     => 'TELEFONE',
                    'contato'  => $telefone,
                ]);
            }

            $conn->commit();

            return $this->json($response, [
                'status' => true,
                'msg'    => 'Usuário cadastrado com sucesso!',
                'id'     => $idUser,
            ], 200);
        } catch (\Exception $e) {
            if ($conn->isTransactionActive()) {
                $conn->rollBack();
            }
            return $this->json($response, ['status' => false, 'msg' => 'Restrição: ' . $e->getMessage()], 500);
        }
    }

    public function details($request, $response, $args)
    {
        $id = $args['id'] ?? null;

        if (is_null($id) || !is_numeric($id)) {
            return $this->json($response, ['status' => false, 'msg' => 'ID inválido.'], 400);
        }

        // vw_user já traz email/telefone prontos (agregados da tabela contact)
        $user = \App\Database\DB::select('id', 'nome', 'sobrenome', 'cpf', 'rg', 'email', 'telefone', 'ativo', 'administrador')
            ->from('vw_user')
            ->where('id = ' . (int) $id)
            ->fetchAssociative();

        if (!$user) {
            return $this->json($response, ['status' => false, 'msg' => 'Usuário não encontrado.'], 404);
        }

        return $this->json($response, ['status' => true, 'data' => $user], 200);
    }

    public function update($request, $response)
    {
        $form = $request->getParsedBody();

        $id        = $form['id'] ?? null;
        $nome      = trim((string) ($form['nome'] ?? ''));
        $sobrenome = trim((string) ($form['sobrenome'] ?? ''));
        $cpf       = trim((string) ($form['cpf'] ?? ''));
        $rg        = trim((string) ($form['rg'] ?? ''));
        $telefone  = trim((string) ($form['telefone'] ?? ''));
        $email     = trim((string) ($form['email'] ?? ''));
        $senha     = (string) ($form['senhaCadastro'] ?? '');

        if (is_null($id) || !is_numeric($id)) {
            return $this->json($response, ['status' => false, 'msg' => 'Usuário inválido.'], 400);
        }

        $erros = [];
        if ($nome === '')      $erros[] = 'Informe o nome.';
        if ($sobrenome === '') $erros[] = 'Informe o sobrenome.';
        if ($cpf === '')       $erros[] = 'Informe o CPF.';

        if (!empty($erros)) {
            return $this->json($response, ['status' => false, 'msg' => implode(' ', $erros)], 422);
        }

        $conn = \App\Database\DB::connection();

        try {
            $conn->beginTransaction();

            $dadosUsuario = [
                'nome'             => $nome,
                'sobrenome'        => $sobrenome,
                'cpf'              => $cpf,
                'rg'               => $rg !== '' ? $rg : null,
                'data_atualizacao' => date('Y-m-d H:i:s'),
            ];

            // Senha só é alterada se o campo vier preenchido — em branco mantém a atual,
            // já que na edição o campo deixa de ser obrigatório.
            if ($senha !== '') {
                $dadosUsuario['senha'] = password_hash($senha, PASSWORD_DEFAULT);
            }

            $conn->update('users', $dadosUsuario, ['id' => (int) $id]);

            // Upsert simples: apaga os contatos antigos desses tipos e recria com os
            // valores atuais do formulário — evita ter que descobrir se já existe linha.
            $conn->delete('contact', ['id_users' => (int) $id, 'tipo' => 'EMAIL']);
            $conn->delete('contact', ['id_users' => (int) $id, 'tipo' => 'TELEFONE']);

            if ($email !== '') {
                $conn->insert('contact', [
                    'id_users' => (int) $id,
                    'tipo'     => 'EMAIL',
                    'contato'  => $email,
                ]);
            }

            if ($telefone !== '') {
                $conn->insert('contact', [
                    'id_users' => (int) $id,
                    'tipo'     => 'TELEFONE',
                    'contato'  => $telefone,
                ]);
            }

            $conn->commit();

            return $this->json($response, [
                'status' => true,
                'msg'    => 'Usuário atualizado com sucesso!',
                'id'     => $id,
            ], 200);
        } catch (\Exception $e) {
            if ($conn->isTransactionActive()) {
                $conn->rollBack();
            }
            return $this->json($response, ['status' => false, 'msg' => 'Restrição: ' . $e->getMessage()], 500);
        }
    }

    public function delete($request, $response)
    {
        $form = $request->getParsedBody();
        $id = $form['id'] ?? null;
        if (is_null($id) || $id === '') {
            return $this->json($response, ['status' => false, 'msg' => 'Informe o código do usuário', 'id' => 0], 403);
        }
        try {
            $IsDeleted = \App\Database\DB::connection()->delete('users', ['id' => $id]);
            if (!$IsDeleted) {
                return $this->json($response, ['status' => false, 'msg' => 'Restrição: ' . $IsDeleted, 'id' => $id], 403);
            }
            return $this->json($response, ['status' => true, 'msg' => 'Removido com sucesso!', 'id' => $id]);
        } catch (\Exception $e) {
            return $this->json($response, ['status' => false, 'msg' => 'Restrição: ' . $e->getMessage(), 'id' => 0], 500);
        }
    }

    public function listingdata($request, $response)
    {
        $form = $request->getParsedBody();

        $term   = $form['search']['value'] ?? null;
        $start  = (int) ($form['start']  ?? 0);
        $length = (int) ($form['length'] ?? 10);

        $columns = [
            0 => 'id',
            1 => 'nome',
            2 => 'sobrenome',
            3 => 'cpf',
            4 => 'rg',
            6 => 'ativo',
            5 => 'administrador',
        ];

        $posField = (isset($form['order'][0]['column']) && isset($columns[(int) $form['order'][0]['column']]))
            ? (int) $form['order'][0]['column']
            : 0;

        $orderType  = strtoupper($form['order'][0]['dir'] ?? 'DESC');
        $orderType  = in_array($orderType, ['ASC', 'DESC'], true) ? $orderType : 'DESC';
        $orderField = $columns[$posField];

        try {
            $totalRecords = (int) \App\Database\DB::select('COUNT(*)')
                ->from('users')
                ->fetchOne();

            $query = \App\Database\DB::select('*')->from('users');

            if (!is_null($term) && $term !== '') {
                $query->setParameter('term', '%' . $term . '%');

                $query->where('CAST(id AS TEXT) ILIKE :term')
                    ->orWhere('nome ILIKE :term')
                    ->orWhere('sobrenome ILIKE :term')
                    ->orWhere('cpf ILIKE :term')
                    ->orWhere('rg ILIKE :term')
                    ->orWhere("TO_CHAR(criado_em, 'DD/MM/YYYY HH24:MI:SS') ILIKE :term")
                    ->orWhere("TO_CHAR(atualizado_em, 'DD/MM/YYYY HH24:MI:SS') ILIKE :term");
            }

            $filteredRecords = (int) (clone $query)
                ->select('COUNT(*)')
                ->fetchOne();

            $users = $query
                ->orderBy($orderField, $orderType)
                ->setFirstResult($start)
                ->setMaxResults($length)
                ->fetchAllAssociative();

            $rows = [];
            foreach ($users as $key => $value) {
                $rows[$key] = [
                    $value['id'],
                    $value['nome']     ?? '',
                    $value['sobrenome']     ?? '',
                    $value['cpf']     ?? '',
                    $value['rg']     ?? '',
                    ($value['ativo'] == true) ? 'Ativo' : 'Inativo',
                    ($value['administrador'] == true) ? 'Sim' : 'Não',
                    "<td>
            <button type='button' class='btn btn-sm btn-warning' onclick='EditUser(" . $value['id'] . ");'>
                <i class='fa-solid fa-pen-to-square'></i> Editar
            </button>
            <button type='button' class='btn btn-sm btn-danger' onclick='ShowModal(" . $value['id'] . ");'>
                <i class='fa-solid fa-trash'></i> Excluir
            </button>
        </td>",
                ];
            }
            return $this->json($response, [
                'recordsTotal'    => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data'            => $rows,
            ], 200);
        } catch (\Exception $e) {
            return $this->json($response, [
                'status' => false,
                'msg'    => 'Restrição: ' . $e->getMessage(),
                'id'     => 0,
            ], 500);
        }
    }
}