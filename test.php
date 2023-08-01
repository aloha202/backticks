<?php


    $ereg = '/`~(.*?)~`/s';
    $string = "`~
                `~`~`~`~`~`~`~`~`~  ~`~`~`~`~`~`~`~`~`
            ~`";
    preg_match_all($ereg, $string, $matches);

    print_r($matches);
