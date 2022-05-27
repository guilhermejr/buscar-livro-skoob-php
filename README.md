# buscar-livro-skoob

Busca informações de um livro no skoob

## Dependência

* PHP 7.3+
* php-xml

## Instalação

``` bash
$ composer require guilhermejr/busca-livro-skoob
```

## Exemplo de uso via console

``` bash
$ vendor/bin/buscar-livro array 9788562936524
```

## Exemplo de uso via código
```php
<?php

require 'vendor/autoload.php';

use BuscaLivroSkoob\Buscador;

$buscador = new Buscador("9788562936524");
print_r($buscador->getJSON());
print_r($buscador->getArray());
```

## Contato
Dúvidas e Sugestões favor enviar e-mail para falecom@guilhermejr.net
