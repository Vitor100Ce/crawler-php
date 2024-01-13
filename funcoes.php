<?php

    function getLeilaoLinks($url) {
   
        $curl = curl_init($url);

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

        $array_leilao = array_filter($resultArray, function ($link) {
            return (strpos($link, 'https://amleiloeiro.com.br/leilao/') === 0);
        });

        return $array_leilao;
    }

    function getLoteLinks($url) {
 
        $curl = curl_init($url);


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

        $array_leilao = array_filter($resultArray, function ($link) {
            return (strpos($link, 'https://amleiloeiro.com.br/lote/') === 0);
        });

        return $array_leilao;
    }


    function salvarEmCSV($infoArray) {

        $csvFile = 'informacoes.csv';

        foreach ($infoArray as &$element) {
            $element = utf8_encode(str_replace(["\n", "\r"],'', trim($element)));
        }
    
        $handle = fopen($csvFile, 'a');
    
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($handle, $infoArray, ';');

        fclose($handle);
    }

    function processarLote($url, $tipo) {

    $curl = curl_init($url);

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

        $leilao = [];

        if($tipo == 'encerrados'){

            $leiloes = $xpath->query('//div/span[@class="block line-through"]');
        } elseif($tipo == 'agendados'){

            $leiloes = $xpath->query('//div[not(contains(@class, "bg-[#075E55]"))]/span[@class="block"]');

        }
     
        $infoArray = array();
    
        foreach ($leiloes as $leilao) {

            $info = $leilao->nodeValue;

            $infoArray[] = $info;
        }



        array_unshift($infoArray, $url);

        echo 'Chamou a processarLote ';
        print_r($infoArray);
    
        salvarEmCSV($infoArray);
    }

    function buscaLeiloes($url, $tipo){

        $curl = curl_init($url);

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

        $filteredLinks = array_filter($resultArray, function ($link) {
            return (strpos($link, 'https://amleiloeiro.com.br/leilao/') === 0) ||
                (strpos($link, 'https://amleiloeiro.com.br/encerrados?page') === 0);
        });

        $array_leilao = array();

        foreach ($filteredLinks as $link) {

            if (strpos($link, 'https://amleiloeiro.com.br/leilao/') === 0) {
                $array_leilao[] = $link;

            } elseif (strpos($link, 'https://amleiloeiro.com.br/encerrados?page') === 0) {
       
                $array_leilao  = array_merge($array_leilao , getLeilaoLinks($link));
            }
        }

        $array_lotes = array();

        foreach ($array_leilao as $link) {
        
            $array_lotes = array_merge($array_lotes, getLoteLinks($link));
        }

        foreach ($array_lotes as $link) {
            processarLote($link, $tipo);
        }

    }

 
?>
