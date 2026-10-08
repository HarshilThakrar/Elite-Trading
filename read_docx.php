<?php
$zip = new ZipArchive;
if ($zip->open('elite documentation.docx') === TRUE) {
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    $xml = preg_replace('/<w:p[^>]*>/', "\n", $xml);
    $xml = strip_tags($xml);
    echo $xml;
} else {
    echo 'Failed';
}
