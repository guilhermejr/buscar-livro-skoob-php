<?php

namespace BuscaLivroSkoob;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class Buscador
{
    private $isbn;
    private $client;
    private $crawler;
    private $anoPaginaIdiomaEditora;

    public function __construct(string $isbn)
    {
        $this->isbn = $isbn;
        $this->client = new Client();
        $this->crawler = new Crawler();
    }

    public function getJSON(bool $cli = false) : string
    {
        if ($cli) {
            return json_encode($this->getArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode($this->getArray());
        }
    }

    public function getArray() : array
    {
        $livro = [];
        $html = $this->htmlDoLivro();

        if ($html == "ISBN inexistente no Skoob") {
            $livro['retorno'] = false;
        } else {

            $this->crawler->addHtmlContent($html, 'ISO-8859-1');
            $this->anoPaginaIdiomaEditora = explode('<br>', $this->crawler->filter('.sidebar-desc')->eq(0)->html());

            $livro['retorno'] = true;
            $livro['capa'] = $this->getCapa();
            $livro['extensao'] = $this->getExtensao();
            $livro['titulo'] = $this->getTitulo();
            $livro['subTitulo'] = $this->getSubTitulo();
            $livro['isbn13'] = $this->getIsbn13();
            $livro['isbn10'] = $this->getIsbn10();
            $livro['descricao'] = $this->getDescricao();
            $livro['genero'] = $this->getGenero();
            $livro['autor'] = $this->getAutor();
            $livro['editora'] = $this->getEditora();
            $livro['idioma'] = $this->getIdioma();
            $livro['anoPublicacao'] = $this->getAnoPublicacao();
            $livro['paginas'] = $this->getPaginas();

        }
        
        return $livro;
    }

    private function getPaginas() : string
    {
        $paginas = trim(explode(':', explode('/', $this->anoPaginaIdiomaEditora[2])[1])[1]);
        return $paginas;
    }

    private function getAnoPublicacao() : string
    {
        $anoPublicacao = trim(explode(':', explode('/', $this->anoPaginaIdiomaEditora[2])[0])[1]);
        return $anoPublicacao;
    }

    private function getIdioma() : string
    {
        $idioma = trim(explode(':', $this->anoPaginaIdiomaEditora[3])[1]);
        return $idioma;
    }

    private function getEditora() : string
    {
        if ($this->getIsbn10()) {
            if ($this->crawler->filter('.sidebar-desc')->eq(0)->filter('a')->eq(0)->count() > 0) {
                $editora = trim($this->crawler->filter('.sidebar-desc')->eq(0)->filter('a')->eq(0)->html());
            } else {
                $editora = trim(explode(':', $this->anoPaginaIdiomaEditora[4])[1]);
            }
        } else {
            $editora = trim(explode(':', $this->anoPaginaIdiomaEditora[3])[1]);
        }
        return $editora;
    }

    private function getAutor() : array
    {
        $autor = $this->crawler->filterXPath('//*[@id="pg-livro-menu-principal-container"]/a')->filter('a')->each(function (Crawler $node, $i) {
            return $node->text();
        });

        return $autor;
    }

    private function getGenero() : array
    {
        try {
            $genero = array_map('trim', explode(' / ', strip_tags($this->crawler->filter('.pg-livro-generos')->eq(0)->html())));
            return $genero;
        } catch (\Exception $e) {
            return "Não informado";
        }
    }

    private function getDescricao() : string
    {
        $descricao = trim(strip_tags(explode('<span class=', $this->crawler->filter('p[itemprop=description]')->eq(0)->html())[0]));
        return empty($descricao) ? "" : $descricao;
    }

    private function getIsbn10() : string
    {
        $isbn10 = trim($this->crawler->filter('.sidebar-desc')->eq(0)->filter('span')->eq(1)->html());
        return empty($isbn10) ? "" : $isbn10;
    }

    private function getIsbn13() : string
    {
        $isbn13 = trim($this->crawler->filter('.sidebar-desc')->eq(0)->filter('span')->eq(0)->html());
        return empty($isbn13) ? "" : $isbn13;
    }

    private function getSubTitulo() : string
    {
        try {
            $subTitulo = trim($this->crawler->filter('.sidebar-subtitulo')->eq(0)->html());
        } catch(\Exception $e) {
            $subTitulo = "";
        }
        
        return $subTitulo;
    }

    private function getTitulo() : string
    {
        $titulo = trim($this->crawler->filter('.sidebar-titulo')->eq(0)->html());
        return empty($titulo) ? "" : $titulo;
    }

    private function getExtensao() : string
    {
        if ($this->getCapa()) {
            $capa = explode('.', $this->getCapa());
            return end($capa);
        } else {
            return "";
        }
    }

    private function getCapa() : string
    {
        $capa = trim($this->crawler->filter('.capa-link-item')->eq(0)->filter('img')->attr('src'));
        return empty($capa) ? "" : $capa;
    }

    private function htmlDoLivro() : string
    {

        $url = $this->urlDoLivro($this->isbn);

        if ($url == "ISBN inexistente no Skoob") {
            return $url;
        }

        $response = $this->client->request('GET', $url);
        $html = $response->getBody();

        return $html;
        
    }

    private function urlDoLivro() : string 
    {

        $crawler = new Crawler();

        $response = $this->client->request('POST', 'https://www.skoob.com.br/livro/lista/', [
            'form_params' => [
                'data[Busca][tag]' => $this->isbn
            ]
        ]);
        
        $html = $response->getBody();
        $crawler->addHtmlContent($html);
        
        if ($crawler->filter('.alert')->count() > 0) {
            return "ISBN inexistente no Skoob";
        }

        $link = $crawler->filter('.detalhes')->eq(0)->filter('a')->attr('href');

        return "https://www.skoob.com.br" . $link;
    }

}

