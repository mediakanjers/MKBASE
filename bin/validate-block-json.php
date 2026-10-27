<?php
    /**
     * Controleert dat elk template-parts/blocks/*\/block.json de velden
     * bevat die MKBase erop verwacht, en dat de opgegeven renderTemplate
     * daadwerkelijk bestaat. Bedoeld om vanuit CI aangeroepen te worden.
     */
    $files = glob(__DIR__ . '/../template-parts/blocks/*/block.json');
    $errors = [];

    foreach ($files as $file) {
        $dir  = dirname($file);
        $json = json_decode(file_get_contents($file), true);

        if ($json === null) {
            $errors[] = "$file: ongeldige JSON";
            continue;
        }

        foreach (['name', 'title', 'apiVersion'] as $key) {
            if (!isset($json[$key])) {
                $errors[] = "$file: verplichte key '$key' ontbreekt";
            }
        }

        if (!isset($json['acf']['renderTemplate'])) {
            $errors[] = "$file: verplichte key 'acf.renderTemplate' ontbreekt";
            continue;
        }

        $renderPath = $dir . '/' . $json['acf']['renderTemplate'];
        if (!file_exists($renderPath)) {
            $errors[] = "$file: renderTemplate '{$json['acf']['renderTemplate']}' bestaat niet ($renderPath)";
        }
    }

    if ($errors) {
        foreach ($errors as $error) {
            echo "FOUT: $error\n";
        }
        exit(1);
    }

    echo 'Alle block.json-bestanden zijn geldig (' . count($files) . " gecontroleerd).\n";
    exit(0);
?>
