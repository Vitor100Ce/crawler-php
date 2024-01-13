<?php

function getLeilaoLinks($url) {
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
 
    $csvFile = 'leilao-lotes.csv';

    // Itera sobre os elementos do array, converte para UTF-8 e remove quebras de linha
    foreach ($infoArray as &$element) {
        $element = utf8_encode(str_replace(["\n", "\r"], '', trim($element)));
    }

    // Abre ou cria o arquivo CSV para escrita
    $handle = fopen($csvFile, 'a');

    // Define a codificação UTF-8
    fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

    // Escreve os dados no arquivo CSV
    fputcsv($handle, $infoArray, ';');

    // Fecha o arquivo CSV
    fclose($handle);
}


function processarLote($url, $tipo) {
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

    echo 'Chamou a processarLote ';
    print_r($infoArray);

    salvarEmCSV($infoArray);
}


function buscaLeiloes($url, $tipo) {
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
