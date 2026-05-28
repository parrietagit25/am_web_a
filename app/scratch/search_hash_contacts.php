<?php
function searchDir($dir) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            if (strpos($content, '#contactos') !== false) {
                $rel = str_replace('c:/Users/pedro.arrieta/Desktop/pagina_nueva/web_am_a/', '', $file->getPathname());
                echo "Found #contactos in $rel\n";
            }
        }
    }
}
searchDir('c:/Users/pedro.arrieta/Desktop/pagina_nueva/web_am_a/app');
