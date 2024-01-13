<?php

function getLeilaoLinks($url) {

    echo 'Obtendo link do leilão' . "\n";

    $curl = curl_init($url);

    // Desativa verificações SSL. Pode testar com o parâmetro true, se caso não der, altere novamente para false
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $html = curl_exec($curl);

    if (curl_errno($curl)) {
        echo 'Erro ao acessar a página: ' . curl_error($curl);
        exit;
    }

    curl_close($curl);

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();

    $links = $dom->getElementsByTagName('a');
    $resultArray = array();

    // Itera sobre as tags <a> e extrai os URLs
    foreach ($links as $link) {
        $resultArray[] = $link->getAttribute('href');
    }

    $resultArray = array_unique($resultArray);

    // Filtra os links de leilão
    $array_leilao = array_filter($resultArray, function ($link) {
        return (strpos($link, 'https://amleiloeiro.com.br/leilao/') === 0);
    });

    return $array_leilao;
}


function getLoteLinks($url) {

    echo 'Obtendo link do lote' . "\n";

    $curl = curl_init($url);

    // Desativa verificações SSL. Pode testar com o parâmetro true, se caso não der, altere novamente para false
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $html = curl_exec($curl);

    if (curl_errno($curl)) {
        echo 'Erro ao acessar a página: ' . curl_error($curl);
        exit;
    }

    curl_close($curl);

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();

    $links = $dom->getElementsByTagName('a');
    $resultArray = array();

    // Itera sobre as tags <a> e extrai os URLs
    foreach ($links as $link) {
        $resultArray[] = $link->getAttribute('href');
    }

    $resultArray = array_unique($resultArray);

    // Filtra os links de lote
    $array_leilao = array_filter($resultArray, function ($link) {
        return (strpos($link, 'https://amleiloeiro.com.br/lote/') === 0);
    });

    return $array_leilao;
}

function salvarEmCSV($infoArray) {
    echo 'Salvando no CSV' . "\n";

    $csvFile = 'leilao-lotes.csv';

    // Verifica se o arquivo já existe
    $fileExists = file_exists($csvFile);

    // Abre ou cria o arquivo CSV para escrita
    $handle = fopen($csvFile, 'a');

    // Se o arquivo não existir, adiciona os títulos
    if (!$fileExists) {
        $titulos = array(
            'link_lote',
            'primeiro_leilao_data',
            'primeiro_leilao_valor',
            'segundo_leilao_data',
            'segundo_leilao_valor'
        );
        fputcsv($handle, $titulos, ';');
    }

    // Extrai a data e o valor da segunda posição do array (se existir)
    if (isset($infoArray[2])) {
        $dataEValorSegundoLeilao = extrairDataEValor($infoArray[2]);
        $infoArray[3] = $dataEValorSegundoLeilao['data'];
        $infoArray[4] = $dataEValorSegundoLeilao['valor'];
    } else {
        // Se não houver segundo leilão, define valores vazios
        $infoArray[3] = '';
        $infoArray[4] = '';
    }

    // Extrai a data e o valor da primeira posição do array
    $dataEValorPrimeiroLeilao = extrairDataEValor($infoArray[1]);
    $infoArray[1] = $dataEValorPrimeiroLeilao['data'];
    $infoArray[2] = $dataEValorPrimeiroLeilao['valor'];

    // Itera sobre os elementos do array, converte para UTF-8 e remove quebras de linha
    foreach ($infoArray as &$element) {
        $element = utf8_encode(str_replace(["\n", "\r"], '', trim($element)));
    }

    // Escreve os dados no arquivo CSV
    fputcsv($handle, $infoArray, ';');

    // Fecha o arquivo CSV
    fclose($handle);
}

function extrairDataEValor($str) {
    // Extrai a data no formato dd/mm/aaaa usando expressão regular
    preg_match('/(\d{2}\/\d{2}\/\d{4})/', $str, $matches);
    $data = $matches[0];

    // Extrai o valor após "R$"
    $valor = trim(str_replace('.', '', substr($str, strpos($str, 'R$') + 2)));

    return array('data' => $data, 'valor' => $valor);
}


function processarLote($url, $tipo) {

    echo 'Obtendo informações do lote' . "\n";

    $curl = curl_init($url);

    // Desativa verificações SSL. Pode testar com o parâmetro true, se caso não der, altere novamente para false
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $html = curl_exec($curl);

    if (curl_errno($curl)) {
        echo 'Erro ao acessar a página: ' . curl_error($curl);
        exit;
    }

    curl_close($curl);

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $leiloes = [];

    // Seleciona os elementos específicos dependendo do tipo (encerrados ou agendados)
    if ($tipo == 'encerrados') {
        $leiloes = $xpath->query('//div/span[@class="block line-through"]');
    } elseif ($tipo == 'agendados') {
        $leiloes = $xpath->query('//div[not(contains(@class, "bg-[#075E55]"))]/span[@class="block"]');
    }

    $infoArray = array();

    // Itera sobre os leilões e extrai as informações
    foreach ($leiloes as $leilao) {
        $info = $leilao->nodeValue;
        $infoArray[] = $info;
    }

    // Adiciona o URL no início do array
    array_unshift($infoArray, $url);

    salvarEmCSV($infoArray);
}


function buscaLeiloes($url, $tipo) {

    echo 'Buscando leilões' . ' ' . $tipo . "\n";

    $curl = curl_init($url);

    // Desativa verificações SSL. Pode testar com o parâmetro true, se caso não der, altere novamente para false
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $html = curl_exec($curl);

    if (curl_errno($curl)) {
        echo 'Erro ao acessar a página: ' . curl_error($curl);
        exit;
    }

    curl_close($curl);

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();

    $links = $dom->getElementsByTagName('a');
    $resultArray = array();

    foreach ($links as $link) {
        $resultArray[] = $link->getAttribute('href');
    }

    $resultArray = array_unique($resultArray);

    // Filtra somente os links de leilão e de páginação
    $filteredLinks = array_filter($resultArray, function ($link) {
        return (strpos($link, 'https://amleiloeiro.com.br/leilao/') === 0) ||
               (strpos($link, 'https://amleiloeiro.com.br/encerrados?page') === 0);
    });

    $array_leilao = array();

    // Itera sobre os links filtrados
    foreach ($filteredLinks as $link) {
        if (strpos($link, 'https://amleiloeiro.com.br/leilao/') === 0) {
            $array_leilao[] = $link;
        } elseif (strpos($link, 'https://amleiloeiro.com.br/encerrados?page') === 0) {
            // Acessa a página e obtém os links de leilão
            $array_leilao  = array_merge($array_leilao , getLeilaoLinks($link));
        }
    }

    $array_lotes = array();

    // Itera sobre os links de leilão e obtém os links de lotes
    foreach ($array_leilao as $link) {
        $array_lotes = array_merge($array_lotes, getLoteLinks($link));
    }

    // Itera sobre os links de lotes e processa cada um
    foreach ($array_lotes as $link) {
        processarLote($link, $tipo);
    }
}
