<?php

declare(strict_types=1);

arch('Todos os arquivos usam strict types')
    ->expect('App')
    ->toUseStrictTypes();

it('Sem debug no código de produção', function () {
    $functions   = ['var_dump', 'dd', 'dump', 'print_r', 'dump_r', 'die', 'exit'];

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(__DIR__ . '/../../App')
    );

    $found = [];

    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') continue;

        $content = file_get_contents($file->getRealPath());
        $tokens  = token_get_all($content);

        foreach ($tokens as $i => $token) {
            if (!is_array($token)) continue;

            // Detecta die/exit pelo token T_EXIT
            if ($token[0] === T_EXIT) {
                $found[] = sprintf(
                    '%s na linha %d → %s',
                    str_replace(__DIR__ . '/../../', '', $file->getRealPath()),
                    $token[2],
                    $token[1]
                );
                continue;
            }

            // Detecta funções de debug comuns
            if ($token[0] !== T_STRING) continue;
            if (!in_array($token[1], $functions, true)) continue;

            for ($j = $i + 1; $j < count($tokens); $j++) {
                if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) continue;
                if ($tokens[$j] === '(') {
                    $found[] = sprintf(
                        '%s na linha %d → %s()',
                        str_replace(__DIR__ . '/../../', '', $file->getRealPath()),
                        $token[2],
                        $token[1]
                    );
                }
                break;
            }
        }
    }

    expect($found)->toBeEmpty(
        "Funções de debug encontradas:\n" . implode("\n", $found)
    );
});

arch('Sem funções perigosas')
    ->expect('App')
    ->not->toUse([
        'eval',
        'exec',
        'shell_exec',
        'system',
        'passthru',
        'proc_open',
        'popen',
    ]);

//Controllers

arch('Controllers devem ser classes finais')
    ->expect('App\Controller')
    ->toBeFinal()
    ->ignoring('App\Controller\Base');

arch('Controllers não acessam banco diretamente')
    ->expect('App\Controller')
    ->not->toUse(['PDO']);


//Database
arch('Database não depende de Controllers')
    ->expect('App\Database')
    ->not->toUse('App\Controller');

arch('Database não depende de Middleware')
    ->expect('App\Database')
    ->not->toUse('App\Middleware');


// Middleware
arch('Middleware não acessa banco diretamente')
    ->expect('App\Middleware')
    ->not->toUse(['PDO']);

arch('Middleware não depende de Controllers')
    ->expect('App\Middleware')
    ->not->toUse('App\Controller');


//Helpers
arch('Helpers não dependem de Controllers')
    ->expect('App\Helpers')
    ->not->toUse('App\Controller');

arch('Helpers não acessam banco diretamente')
    ->expect('App\Helpers')
    ->not->toUse(['PDO']);

//Traits
arch('Traits devem ser traits')
    ->expect('App\Trait')
    ->toBeTraits();

//Routes
arch('Routes não acessam banco diretamente')
    ->expect('App\Routes')
    ->not->toUse(['PDO']);

arch('Routes não instanciam Database diretamente')
    ->expect('App\Routes')
    ->not->toUse('App\Database');
