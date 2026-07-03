<?php

declare(strict_types=1);

namespace App\Controller;

final class Login extends Base
{
    public function login($request, $response)
    {
        try {
            return $this->getTwig()
                ->render($response, $this->setView('login'), [
                    'titulo' => 'Início',
                ])
                ->withHeader('Content-Type', 'text/html')
                ->withStatus(200);
        } catch (\Exception $e) {
        }
    }

    public function authenticate($request, $response)
    {
        $form  = $request->getParsedBody();
        $login = $form['login'] ?? null;
        $senha = $form['senha'] ?? null;

        if (is_null($login) || is_null($senha)) {
            return $this->json($response, ['status' => false, 'msg' => 'Por favor informe seu usuário e senha!', 'id' => 0]);
        }


        if (isset($_SESSION['login_locked_until']) && $_SESSION['login_locked_until'] > time()) {
            return $this->json($response, ['status' => false, 'msg' => 'Muitas tentativas. Tente novamente em alguns minutos.', 'id' => 0], 429);
        }

        try {
            $qb    = \App\Database\DB::select('*')->from('vw_user');
            $login = $qb->createNamedParameter($login);

            $qb->where('cpf = ' . $login)
                ->orWhere('email = '    . $login)
                ->orWhere('telefone = ' . $login);

            $user = $qb->fetchAssociative();

            $dummyHash   = '$2y$10$CwTycUXWue0Thq9StjUM0uJ8.k3.kK1m3Sv7lJ1uG9N9Yvb.MqYsa';
            $senhaValida = password_verify($senha, $user['senha'] ?? $dummyHash);

            if (!$user || !$senhaValida) {
                $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                if ($_SESSION['login_attempts'] >= 5) {
                    $_SESSION['login_locked_until'] = time() + 900;
                    $_SESSION['login_attempts']     = 0;
                }
                return $this->json($response, ['status' => false, 'msg' => 'Verifique seu e-mail e senha e tente novamente!', 'id' => 0], 403);
            }

            if (!$user['ativo']) {
                return $this->json($response, [
                    'status' => false,
                    'msg'    => 'Seu acesso ainda não foi liberado. Aguarde a aprovação do administrador.',
                    'id'     => 0,
                ], 403);
            }

            unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);
            session_regenerate_id(true);

            if (password_needs_rehash($user['senha'], PASSWORD_DEFAULT)) {
                \App\Database\DB::connection()->update(
                    'users',
                    [
                        'senha'            => password_hash($senha, PASSWORD_DEFAULT),
                        'data_atualizacao' => date('Y-m-d H:i:s'),
                    ],
                    ['id' => $user['id']],
                );
            }

            unset($user['senha']);

            $_SESSION['user']           = $user;
            $_SESSION['user']['logado'] = true;

            $lifetime = (int) (ini_get('session.gc_maxlifetime') ?: 3600);

            $payload = [
                'iat' => time(),
                'exp' => time() + $lifetime,
                'sub' => (string) $user['id'],
            ];

            $jwt      = \Firebase\JWT\JWT::encode($payload, SECRET_KEY, 'HS256');
            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;

            setcookie('auth_token', $jwt, [
                'expires'  => time() + $lifetime,
                'path'     => '/',
                'domain'   => $_SERVER['HTTP_HOST'],
                'secure'   => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            $_SESSION['user']['sessao_criada_em'] = (new \DateTime())->format('Y-m-d H:i:s');
            $_SESSION['user']['sessao_expira_em'] = (new \DateTime())->modify("+{$lifetime} seconds")->format('Y-m-d H:i:s');

            return $this->json($response, [
                'status'           => true,
                'msg'              => 'Seja bem vindo de volta!',
                'id'               => $user['id'],
                'redirect'         => $user['administrador'] ? '/admin/gestao' : '/home',
                'sessao_expira_em' => $_SESSION['user']['sessao_expira_em'],
            ], 200);
        } catch (\PDOException $e) {
            error_log('[auth][DB] ' . $e->getMessage());
            return $this->json($response, ['status' => false, 'msg' => 'Não foi possível concluir o login. Tente novamente.', 'id' => 0], 500);
        } catch (\UnexpectedValueException | \DomainException $e) {
            error_log('[auth][JWT] ' . $e->getMessage());
            return $this->json($response, ['status' => false, 'msg' => 'Não foi possível concluir o login. Tente novamente.', 'id' => 0], 500);
        } catch (\Throwable $e) {
            error_log('[auth][GERAL] ' . $e->getMessage());
            return $this->json($response, ['status' => false, 'msg' => 'Erro inesperado. Tente novamente.', 'id' => 0], 500);
        }
    }

    public function logout($request, $response)
    {
        $_SESSION = [];
        session_destroy();

        setcookie('auth_token', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'domain'   => $_SERVER['HTTP_HOST'],
            'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        return $response
            ->withHeader('Location', '/login')
            ->withStatus(302);
    }

    public function preRegister($request, $response)
    {
        $form = $request->getParsedBody();

        $nome      = $form['nome']          ?? null;
        $sobrenome = $form['sobrenome']      ?? null;
        $cpf       = $form['cpf']           ?? null;
        $rg        = $form['rg']            ?? null;
        $senha     = $form['senhaCadastro'] ?? null;
        $email     = $form['email']         ?? null;
        $telefone  = $form['telefone']      ?? null;

        $DataUser = [
            'nome'             => $nome,
            'sobrenome'        => $sobrenome,
            'cpf'              => $cpf,
            'rg'               => $rg,
            'senha'            => password_hash($senha, PASSWORD_DEFAULT),
            'data_cadastro'    => date('Y-m-d H:i:s'),
            'data_atualizacao' => date('Y-m-d H:i:s'),
        ];


        $conn = \App\Database\DB::connection();

        try {
            $conn->beginTransaction();

            $conn->insert('users', $DataUser);
            $id_usuario = (int) $conn->lastInsertId();

            if (!empty($email)) {
                $conn->insert('contact', [
                    'id_users' => $id_usuario,
                    'tipo'     => 'EMAIL',
                    'contato'  => $email,
                ]);
            }

            if (!empty($telefone)) {
                $conn->insert('contact', [
                    'id_users' => $id_usuario,
                    'tipo'     => 'TELEFONE',
                    'contato'  => $telefone,
                ]);
            }

            $conn->commit();

            return $this->json($response, [
                'status' => true,
                'msg'    => 'Pré-cadastro realizado com sucesso!',
                'id'     => $id_usuario,
            ], 201);
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            $conn->rollBack();
            return $this->json($response, [
                'status' => false,
                'msg'    => 'CPF ou contato já cadastrado.',
                'id'     => 0,
            ], 409);
        } catch (\Throwable $e) {
            $conn->rollBack();
            error_log('[preRegister] ' . $e->getMessage());
            return $this->json($response, [
                'status' => false,
                'msg'    => 'Erro ao realizar pré-cadastro. Tente novamente.',
                'id'     => 0,
            ], 500);
        }
    }

    public function google($request, $response)
    {
        $form = $request->getParsedBody();

        $credential           = $form['credential']     ?? null;
        $form_g_csrf_token    = $form['g_csrf_token']    ?? null;
        $cookie_g_csrf_token  = $_COOKIE['g_csrf_token'] ?? null;
        $google_client_id     = $_ENV['GOOGLE_CLIENT_ID'] ?? null;

        // Valida presença dos dados obrigatórios
        if (is_null($credential) || is_null($form_g_csrf_token) || is_null($cookie_g_csrf_token)) {
            return $response->withHeader('Location', '/login?google=erro')->withStatus(302);
        }

        // Valida o CSRF token do Google (cookie deve bater com o campo do formulário)
        if (!hash_equals($cookie_g_csrf_token, $form_g_csrf_token)) {
            return $response->withHeader('Location', '/login?google=erro')->withStatus(302);
        }

        try {
            $provider = new \League\OAuth2\Client\Provider\Google([
                'clientId'     => $google_client_id,
                'clientSecret' => '',
                'redirectUri'  => '',
            ]);

            $httpResponse = $provider->getHttpClient()->request(
                'GET',
                'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential),
                ['timeout' => 3, 'connect_timeout' => 2]
            );

            $claims = json_decode((string) $httpResponse->getBody(), true, flags: JSON_THROW_ON_ERROR);

            // Valida que o token foi emitido para o seu app
            if (($claims['aud'] ?? '') !== $google_client_id) {
                return $response->withHeader('Location', '/login?google=erro')->withStatus(302);
            }

            $email = $claims['email'] ?? null;

            if (is_null($email)) {
                return $response->withHeader('Location', '/login?google=erro')->withStatus(302);
            }

            // Busca o usuário na vw_user pelo e-mail do Google
            $qb = \App\Database\DB::select('*')->from('vw_user');
            $qb->where('email = ' . $qb->createNamedParameter($email));
            $user = $qb->fetchAssociative();

            // Nenhuma conta encontrada com esse e-mail: cria automaticamente,
            // já como inativa, aguardando aprovação do administrador.
            if (!$user) {
                $nome      = $claims['given_name']  ?? '';
                $sobrenome = $claims['family_name'] ?? '';

                $conn = \App\Database\DB::connection();

                try {
                    $conn->beginTransaction();

                    $conn->insert('users', [
                        'nome'             => $nome,
                        'sobrenome'        => $sobrenome,
                        'cpf'              => null,
                        'rg'               => null,
                        'senha'            => null, // login via Google não usa senha local
                        'ativo'            => false,
                        'administrador'    => false,
                        'data_cadastro'    => date('Y-m-d H:i:s'),
                        'data_atualizacao' => date('Y-m-d H:i:s'),
                    ]);
                    $idUsuario = (int) $conn->lastInsertId();

                    $conn->insert('contact', [
                        'id_users' => $idUsuario,
                        'tipo'     => 'EMAIL',
                        'contato'  => $email,
                    ]);

                    $conn->commit();
                } catch (\Throwable $e) {
                    $conn->rollBack();
                    error_log('[auth][GOOGLE][criar_usuario] ' . $e->getMessage());
                    return $response->withHeader('Location', '/login?google=erro')->withStatus(302);
                }

                // Conta recém-criada: sempre precisa de aprovação do administrador.
                return $response->withHeader('Location', '/login?google=pendente')->withStatus(302);
            }

            // Conta já existe, mas ainda não foi aprovada pelo administrador
            if (!$user['ativo']) {
                return $response->withHeader('Location', '/login?google=pendente')->withStatus(302);
            }

            // Login válido — cria a sessão
            session_regenerate_id(true);

            unset($user['senha']);
            $_SESSION['user']           = $user;
            $_SESSION['user']['logado'] = true;

            $lifetime = (int) (ini_get('session.gc_maxlifetime') ?: 3600);

            $payload_jwt = [
                'iat' => time(),
                'exp' => time() + $lifetime,
                'sub' => (string) $user['id'],
            ];

            $jwt      = \Firebase\JWT\JWT::encode($payload_jwt, SECRET_KEY, 'HS256');
            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;

            setcookie('auth_token', $jwt, [
                'expires'  => time() + $lifetime,
                'path'     => '/',
                'domain'   => $_SERVER['HTTP_HOST'],
                'secure'   => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            $_SESSION['user']['sessao_criada_em'] = (new \DateTime())->format('Y-m-d H:i:s');
            $_SESSION['user']['sessao_expira_em'] = (new \DateTime())->modify("+{$lifetime} seconds")->format('Y-m-d H:i:s');

            // Direciona para /admin/gestao se administrador, ou /home para usuários comuns
            $destino = $user['administrador'] ? '/admin/gestao' : '/home';

            return $response
                ->withHeader('Location', $destino)
                ->withStatus(302);
        } catch (\Throwable $e) {
            error_log('[auth][GOOGLE] ' . $e->getMessage());
            return $response->withHeader('Location', '/login?google=erro')->withStatus(302);
        }
    }
}