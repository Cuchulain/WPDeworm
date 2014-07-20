<?php
/**
 * Created by PhpStorm.
 * User: Jan Čejka
 * Site: http://jancejka.cz
 * Date: 17.7.14
 * Time: 21:49
 */

$vzory = array(
    'jmeno' => '/^\s*Plugin Name:\s+(.+)$/im',
    'url'   => '/^\s*Plugin URI:\s+(\S+)/im'
);

$dwn_dir = 'plugins-download';
$dwn_url_mask = 'http://downloads.wordpress.org/plugin/%s.zip';

function nactiHodnoty($obsah, $vzory) {
    $hodnoty = array();

    foreach ($vzory as $vzor_nazev => $vzor_maska) {
        $hodnoty[$vzor_nazev] = '';
        if( preg_match($vzor_maska, $obsah, $shody) === 1 ) {
            $hodnoty[$vzor_nazev] = $shody[1];
        }
    }

    return $hodnoty;
}

function zpracujSoubor( $fullpath ) {
    global $vzory;
    global $dwn_dir, $dwn_url_mask;

    $obsah = file_get_contents($fullpath);
    $hodnoty = nactiHodnoty($obsah, $vzory);

    if( $hodnoty['url'] != '') {
        $subdir = preg_replace('@^\.[/\\\\]@', '', dirname(dirname($fullpath)));

        $fileBaseName = basename(dirname(($fullpath)));
        $localDir = sprintf("%s/%s",
            $dwn_dir, $subdir);
        $localFilePath = sprintf("%s/%s.zip",
            $localDir, $fileBaseName);

        echo(sprintf("<a href=\"%s\" target=\"_blank\">%s</a> : %s -&gt; %s\n<br />",
            $hodnoty['url'], $hodnoty['jmeno'], dirname($fullpath), $localFilePath));

        if( ($localDir != '') && !file_exists($localDir) ) {
            mkdir($localDir, 0777, true);
        }

        if( !file_exists($localFilePath) ) {
            download(sprintf($dwn_url_mask, $fileBaseName), $localFilePath);
        }

    }

}

function projdi($dir) {
    $Directory = new RecursiveDirectoryIterator($dir);
    $Iterator = new RecursiveIteratorIterator($Directory);
    $Regex = new RegexIterator($Iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

    foreach( $Regex as $soubor ) {
        zpracujSoubor($soubor[0]);
    }
}

function download($url, $file) {
    $ch = curl_init($url);

    $fp = fopen($file, "w");

    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    //curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

    curl_exec($ch);
    fclose($fp);

    $ret = (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200);
    curl_close($ch);

    if( !$ret ) {
        unlink($file);
    }

    return $ret;
}


// script start here

set_time_limit(600);

if( !file_exists($dwn_dir) ) {
    mkdir($dwn_dir);
}

projdi('.');

