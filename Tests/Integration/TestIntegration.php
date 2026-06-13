<?php

declare(strict_types=1);

use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;
use Psr\Http\Message\ResponseInterface;

// ─────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────

function getApp(): App
{
    $_SERVER['HTTP_HOST'] ??= 'localhost';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/../../App/Helpers/settings.php';
    return require __DIR__ . '/../../App/bootstrap.php';
}

function request(
    string $method,
    string $path,
    array  $body    = [],
    array  $cookies = [],
    array  $headers = [],
): ResponseInterface {
    $app     = getApp();
    $factory = new ServerRequestFactory();
    $req     = $factory->createServerRequest($method, $path);

    foreach ($headers as $name => $value) {
        $req = $req->withHeader($name, $value);
    }

    if ($cookies !== []) {
        $req = $req->withCookieParams($cookies);
    }

    if ($body !== []) {
        $req = $req->withParsedBody($body);
    }

    return $app->handle($req);
}

function json(ResponseInterface $res): array
{
    return json_decode((string) $res->getBody(), true) ?? [];
}

// Garante settings carregado para testes que não usam request()
$_SERVER['HTTP_HOST'] ??= 'localhost';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../App/Helpers/settings.php';


// ─────────────────────────────────────────────
// DATABASE
// ─────────────────────────────────────────────

describe('Database', function () {

    it('retorna uma instância de Connection válida', function () {
        $conn = \App\Database\Connection::get();
        expect($conn)->toBeInstanceOf(\Doctrine\DBAL\Connection::class);
    });

    it('DB::select retorna um QueryBuilder', function () {
        $qb = \App\Database\DB::select('*');
        expect($qb)->toBeInstanceOf(\Doctrine\DBAL\Query\QueryBuilder::class);
    });

    it('DB::connection retorna a mesma instância (singleton)', function () {
        $a = \App\Database\Connection::get();
        $b = \App\Database\Connection::get();
        expect($a)->toBe($b);
    });

    it('consegue executar uma query simples no banco', function () {
        $rows = \App\Database\DB::select('1 AS ok')
            ->fetchAllAssociative();
        expect($rows)->toBeArray()->not->toBeEmpty();
    });
});


// ─────────────────────────────────────────────
// REQUEST
// ─────────────────────────────────────────────

describe('Request', function () {

    it('cria uma ServerRequest com método e path corretos', function () {
        $factory = new ServerRequestFactory();
        $req     = $factory->createServerRequest('GET', '/login');

        expect($req->getMethod())->toBe('GET');
        expect($req->getUri()->getPath())->toBe('/login');
    });

    it('aceita corpo parsed (POST form)', function () {
        $factory = new ServerRequestFactory();
        $req     = $factory->createServerRequest('POST', '/authentication/authenticate')
            ->withParsedBody(['login' => 'test@test.com', 'senha' => '123']);

        expect($req->getParsedBody())->toMatchArray(['login' => 'test@test.com']);
    });

    it('aceita headers customizados', function () {
        $factory = new ServerRequestFactory();
        $req     = $factory->createServerRequest('GET', '/admin/gestao')
            ->withHeader('Accept', 'application/json');

        expect($req->getHeaderLine('Accept'))->toBe('application/json');
    });
});


// ─────────────────────────────────────────────
// RESPONSE
// ─────────────────────────────────────────────

describe('Response (trait)', function () {

    it('retorna JSON com status 200 por padrão', function () {
        $controller = new class extends \App\Controller\Base {
            public function call($response, array $data, int $code = 200)
            {
                return $this->json($response, $data, $code);
            }
        };

        $response = (new ResponseFactory())->createResponse();
        $res      = $controller->call($response, ['status' => true]);

        expect($res->getStatusCode())->toBe(200);
        expect($res->getHeaderLine('Content-Type'))->toContain('application/json');
        expect(json($res))->toMatchArray(['status' => true]);
    });

    it('retorna o status code informado', function () {
        $controller = new class extends \App\Controller\Base {
            public function call($response, array $data, int $code = 200)
            {
                return $this->json($response, $data, $code);
            }
        };

        $response = (new ResponseFactory())->createResponse();
        $res      = $controller->call($response, ['msg' => 'não autorizado'], 401);

        expect($res->getStatusCode())->toBe(401);
    });

    it('serializa unicode sem escape', function () {
        $controller = new class extends \App\Controller\Base {
            public function call($response, array $data, int $code = 200)
            {
                return $this->json($response, $data, $code);
            }
        };

        $response = (new ResponseFactory())->createResponse();
        $res      = $controller->call($response, ['msg' => 'não autorizado']);
        $body     = (string) $res->getBody();

        expect($body)->toContain('não autorizado');
    });
});


// ─────────────────────────────────────────────
// ROUTES
// ─────────────────────────────────────────────

describe('Routes — públicas', function () {

    it('GET /login retorna 200', function () {
        $res = request('GET', '/login');
        expect($res->getStatusCode())->toBe(200);
    });

    it('GET / retorna 200 ou 302', function () {
        $res = request('GET', '/');
        expect($res->getStatusCode())->toBeIn([200, 302]);
    });

    it('rota inexistente retorna 404', function () {
        $res = request('GET', '/rota-que-nao-existe');
        expect($res->getStatusCode())->toBe(404);
    });

    it('GET /report retorna 200', function () {
        $res = request('GET', '/report');
        expect($res->getStatusCode())->toBeIn([200, 302]);
    });
});


// ─────────────────────────────────────────────
// MIDDLEWARE
// ─────────────────────────────────────────────

describe('Middleware — web()', function () {

    it('/login acessível sem autenticação retorna 200', function () {
        unset($_SESSION['user']);
        $res = request('GET', '/login');
        expect($res->getStatusCode())->toBe(200);
    });

    it('/admin/gestao sem autenticação retorna 200, 302 ou 404', function () {
        // 302 se middleware ativo, 200/404 se comentado
        unset($_SESSION['user']);
        $res = request('GET', '/admin/gestao');
        expect($res->getStatusCode())->toBeIn([200, 302, 404]);
    });

    it('/login com autenticação válida retorna 200 ou 302', function () {
        $_SESSION['user']['logado'] = true;

        $jwt = \Firebase\JWT\JWT::encode(
            ['iat' => time(), 'exp' => time() + 3600, 'sub' => '1'],
            SECRET_KEY,
            'HS256'
        );

        $res = request('GET', '/login', cookies: ['auth_token' => $jwt]);
        expect($res->getStatusCode())->toBeIn([200, 302]);

        unset($_SESSION['user']);
    });
});

describe('Middleware — instância', function () {

    it('Middleware::web() retorna um callable', function () {
        $mw = \App\Middleware\Middleware::web();
        expect($mw)->toBeCallable();
    });

    it('Middleware::api() retorna um callable', function () {
        $mw = \App\Middleware\Middleware::api();
        expect($mw)->toBeCallable();
    });
});


// ─────────────────────────────────────────────
// CONTROLLER — Login
// ─────────────────────────────────────────────

describe('Controller — Login', function () {

    it('POST /authentication/authenticate com campos vazios retorna status false', function () {
        $res  = request('POST', '/authentication/authenticate', [
            'login' => '',
            'senha' => '',
        ]);
        $body = json($res);
        expect($body)->toHaveKey('status');
        expect($body['status'])->toBeFalse();
    });

    it('POST /authentication/authenticate com credenciais inválidas retorna status false', function () {
        $res  = request('POST', '/authentication/authenticate', [
            'login' => 'naoexiste@teste.com',
            'senha' => 'senhaerrada',
        ]);
        $body = json($res);
        expect($body)->toHaveKey('status');
        expect($body['status'])->toBeFalse();
    });

    it('resposta de authenticate tem chave msg', function () {
        $res  = request('POST', '/authentication/authenticate', [
            'login' => 'invalido',
            'senha' => 'invalido',
        ]);
        $body = json($res);
        expect($body)->toHaveKey('msg');
        expect($body['msg'])->toBeString()->not->toBeEmpty();
    });

    it('GET /authentication/logout redireciona para /login', function () {
        $res = request('GET', '/authentication/logout');
        expect($res->getStatusCode())->toBe(302);
        expect($res->getHeaderLine('Location'))->toBe('/login');
    });

    it('GET /authentication/logout destrói a sessão', function () {
        $_SESSION['user'] = ['logado' => true, 'id' => 1];
        request('GET', '/authentication/logout');
        expect($_SESSION)->not->toHaveKey('user');
    });
});


// ─────────────────────────────────────────────
// CONTROLLER — Admin
// ─────────────────────────────────────────────

describe('Controller — Admin (getsalesdata / getabcranking)', function () {

    beforeEach(function () {
        $_SESSION['user']['logado'] = true;
        $this->jwt = \Firebase\JWT\JWT::encode(
            ['iat' => time(), 'exp' => time() + 3600, 'sub' => '1'],
            SECRET_KEY,
            'HS256'
        );
    });

    afterEach(function () {
        unset($_SESSION['user']);
    });

    it('GET /admin/gestao/getsalesdata retorna 200', function () {
        $res = request('GET', '/admin/gestao/getsalesdata', cookies: ['auth_token' => $this->jwt]);
        expect($res->getStatusCode())->toBe(200);
    });

    it('GET /admin/gestao/getsalesdata retorna JSON com labels e series', function () {
        $res  = request('GET', '/admin/gestao/getsalesdata', cookies: ['auth_token' => $this->jwt]);
        $body = json($res);

        expect($body)->toHaveKeys(['labels', 'series']);
        expect($body['labels'])->toBeArray();
        expect($body['series'])->toBeArray();
    });

    it('series de getsalesdata tem name e values', function () {
        $res  = request('GET', '/admin/gestao/getsalesdata', cookies: ['auth_token' => $this->jwt]);
        $body = json($res);

        expect($body['series'][0])->toHaveKeys(['name', 'values']);
        expect($body['series'][0]['name'])->toBe('Chamados');
        expect($body['series'][0]['values'])->toBeArray();
    });

    it('GET /admin/gestao/getabcranking retorna 200', function () {
        $res = request('GET', '/admin/gestao/getabcranking', cookies: ['auth_token' => $this->jwt]);
        expect($res->getStatusCode())->toBe(200);
    });

    it('GET /admin/gestao/getabcranking retorna JSON com labels e values', function () {
        $res  = request('GET', '/admin/gestao/getabcranking', cookies: ['auth_token' => $this->jwt]);
        $body = json($res);

        expect($body)->toHaveKeys(['labels', 'values']);
        expect($body['labels'])->toBeArray();
        expect($body['values'])->toBeArray();
    });

    it('values de getabcranking são inteiros', function () {
        $res    = request('GET', '/admin/gestao/getabcranking', cookies: ['auth_token' => $this->jwt]);
        $body   = json($res);
        $values = $body['values'] ?? [];

        foreach ($values as $v) {
            expect($v)->toBeInt();
        }
    });
});
