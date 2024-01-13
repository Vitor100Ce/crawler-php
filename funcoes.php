<?php

    function getLeilaoLinks($url) {
        // Inicializa a cURL
        $curl = curl_init($url);

        // Desabilita a verificação SSL (pode ser arriscado em ambientes de produção)
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        // Configura cURL para retornar o resultado como string
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Executa a solicitação e obtém o conteúdo da página
        $html = curl_exec($curl);

        // Verifica se ocorreu algum erro durante a solicitação
        if (curl_errno($curl)) {
            echo 'Erro ao acessar a página: ' . curl_error($curl);
            exit;
        }

        // Fecha a sessão cURL
        curl_close($curl);

        // Inicializa o DOMDocument para manipulação de HTML
        $dom = new DOMDocument();

        // Habilita a manipulação de HTML válido, mesmo se estiver mal formado
        libxml_use_internal_errors(true);

        // Carrega o conteúdo da URL no DOMDocument
        $dom->loadHTML($html);

        // Desativa os erros do libxml para evitar mensagens indesejadas
        libxml_clear_errors();

        // Obtém todas as tags <a> na página
        $links = $dom->getElementsByTagName('a');
        $resultArray = array();

        // Itera sobre as tags <a> e adiciona os valores ao array
        foreach ($links as $link) {
            // Adiciona o valor do atributo href ao array
            $resultArray[] = $link->getAttribute('href');
        }

        // Remove links duplicados
        $resultArray = array_unique($resultArray);

        // Filtra os links com base no padrão de leilão
        $array_leilao = array_filter($resultArray, function ($link) {
            return (strpos($link, 'https://amleiloeiro.com.br/leilao/') === 0);
        });

        return $array_leilao;
    }

    // Função para acessar uma página e obter os links de leilões
    function getLoteLinks($url) {
        // Inicializa a cURL
        $curl = curl_init($url);

        // Desabilita a verificação SSL (pode ser arriscado em ambientes de produção)
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        // Configura cURL para retornar o resultado como string
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Executa a solicitação e obtém o conteúdo da página
        $html = curl_exec($curl);

        // Verifica se ocorreu algum erro durante a solicitação
        if (curl_errno($curl)) {
            echo 'Erro ao acessar a página: ' . curl_error($curl);
            exit;
        }

        // Fecha a sessão cURL
        curl_close($curl);

        // Inicializa o DOMDocument para manipulação de HTML
        $dom = new DOMDocument();

        // Habilita a manipulação de HTML válido, mesmo se estiver mal formado
        libxml_use_internal_errors(true);

        // Carrega o conteúdo da URL no DOMDocument
        $dom->loadHTML($html);

        // Desativa os erros do libxml para evitar mensagens indesejadas
        libxml_clear_errors();

        // Obtém todas as tags <a> na página
        $links = $dom->getElementsByTagName('a');
        $resultArray = array();

        // Itera sobre as tags <a> e adiciona os valores ao array
        foreach ($links as $link) {
            // Adiciona o valor do atributo href ao array
            $resultArray[] = $link->getAttribute('href');
        }

        // Remove links duplicados
        $resultArray = array_unique($resultArray);

        // Filtra os links com base no padrão de leilão
        $array_leilao = array_filter($resultArray, function ($link) {
            return (strpos($link, 'https://amleiloeiro.com.br/lote/') === 0);
        });

        return $array_leilao;
    }


    function salvarEmCSV($infoArray) {
        // Nome do arquivo CSV
        $csvFile = 'informacoes.csv';
    

        // Convertendo elementos do array para UTF-8 e removendo quebras de linha
        foreach ($infoArray as &$element) {
            $element = utf8_encode(str_replace(["\n", "\r"],'', trim($element)));
        }
    
        // Abre ou cria o arquivo CSV para escrita
        $handle = fopen($csvFile, 'a');
    
        // Definindo a codificação UTF-8
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
    
        // Escreve os dados no arquivo CSV
        fputcsv($handle, $infoArray, ';');
    
        // Fecha o arquivo CSV
        fclose($handle);
    }

    // Função para acessar uma página de leilão, obter informações e salvar em CSV
    function processarLote($url, $tipo) {

    // Inicializa a cURL
    $curl = curl_init($url);

    // Desabilita a verificação SSL (pode ser arriscado em ambientes de produção)
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

    // Configura cURL para retornar o resultado como string
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    // Executa a solicitação e obtém o conteúdo da página
    $html = curl_exec($curl);

    // Verifica se ocorreu algum erro durante a solicitação
    if (curl_errno($curl)) {
        echo 'Erro ao acessar a página: ' . curl_error($curl);
        exit;
    }

    // Fecha a sessão cURL
    curl_close($curl);

        // Inicializa o DOMDocument para manipulação de HTML
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);

        // Carrega o conteúdo da URL no DOMDocument
        $dom->loadHTML($html);

        // Desativa os erros do libxml para evitar mensagens indesejadas
        libxml_clear_errors();

        // Utiliza XPath para obter os elementos relevantes
        $xpath = new DOMXPath($dom);


        $leilao = [];

        if($tipo == 'encerrados'){

        // Extrai os valores usando XPath
            $leiloes = $xpath->query('//div/span[@class="block line-through"]');
        } elseif($tipo == 'agendados'){

            $leiloes = $xpath->query('//div[not(contains(@class, "bg-[#075E55]"))]/span[@class="block"]');

        }
     
        // Array para armazenar as informações
        $infoArray = array();
    
        // Itera sobre os leilões
        foreach ($leiloes as $leilao) {
            // Obtém o texto completo dentro de cada <span> com a classe "block"
            $info = $leilao->nodeValue;

            // Adiciona o texto ao array
            $infoArray[] = $info;
        }


        // Adiciona o URL do lote como a primeira coluna
        array_unshift($infoArray, $url);
    
        // Imprime as informações para teste
        echo 'Chamou a processarLote ';
        print_r($infoArray);
    
        // Salva as informações em um arquivo CSV
        salvarEmCSV($infoArray);
    }

    function buscaLeiloes($url, $tipo){

        // Inicializa a cURL
        $curl = curl_init($url);

        // Desabilita a verificação SSL (pode ser arriscado em ambientes de produção)
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        // Configura cURL para retornar o resultado como string
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Executa a solicitação e obtém o conteúdo da página
        $html = curl_exec($curl);

        // Verifica se ocorreu algum erro durante a solicitação
        if (curl_errno($curl)) {
            echo 'Erro ao acessar a página: ' . curl_error($curl);
            exit;
        }

        // Fecha a sessão cURL
        curl_close($curl);

        // Inicializa o DOMDocument para manipulação d  e HTML
        $dom = new DOMDocument();

        // Habilita a manipulação de HTML válido, mesmo se estiver mal formado
        libxml_use_internal_errors(true);

        // Carrega o conteúdo da URL no DOMDocument
        $dom->loadHTML($html);

        // Desativa os erros do libxml para evitar mensagens indesejadas
        libxml_clear_errors();

        // Obtém todas as tags <a> na página
        $links = $dom->getElementsByTagName('a');
        $resultArray = array();

        // Itera sobre as tags <a> e adiciona os valores ao array
        foreach ($links as $link) {
            // Adiciona o valor do atributo href ao array
            $resultArray[] = $link->getAttribute('href');
        }

        // Remove links duplicados
        $resultArray = array_unique($resultArray);

        // Filtra os links com base nos padrões especificados
        $filteredLinks = array_filter($resultArray, function ($link) {
            return (strpos($link, 'https://amleiloeiro.com.br/leilao/') === 0) ||
                (strpos($link, 'https://amleiloeiro.com.br/encerrados?page') === 0);
        });

        // Separa os links em arrays distintos
        $array_leilao = array();

        foreach ($filteredLinks as $link) {
            if (strpos($link, 'https://amleiloeiro.com.br/leilao/') === 0) {
                $array_leilao[] = $link;
            } elseif (strpos($link, 'https://amleiloeiro.com.br/encerrados?page') === 0) {
                // Acessa a página e obtém os links de leilão
                $array_leilao  = array_merge($array_leilao , getLeilaoLinks($link));
            }
        }

        // Novo array para armazenar os links de lotes
        $array_lotes = array();

        // Itera sobre os links de leilão
        foreach ($array_leilao as $link) {
            // Acessa a página de leilão e obtém os links de lotes
            $array_lotes = array_merge($array_lotes, getLoteLinks($link));
        }

        // Itera sobre os links de lotes
        foreach ($array_lotes as $link) {
            processarLote($link, $tipo);
        }

    }


 
?>
