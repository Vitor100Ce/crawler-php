<?php

    include 'funcoes.php';
  
    buscaLeiloes('https://amleiloeiro.com.br/encerrados', 'encerrados');

    buscaLeiloes('https://amleiloeiro.com.br/agenda', 'agendados');

    echo 'Script finalizado! Verifique o csv'

?>
