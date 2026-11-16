<?php
    /**
     * Controleert de conventie uit DEVELOPMENT.md: een versiebump in style.css
     * en een nieuwe (nog ongedateerde) sectie in CHANGELOG.md horen samen te
     * gaan, en de versienummers moeten overeenkomen. Bedoeld om vanuit CI
     * (pull_request-event) aangeroepen te worden.
     */
    $root = dirname(__DIR__);
    $base = getenv('GITHUB_BASE_REF');

    if (!$base) {
        echo "Geen GITHUB_BASE_REF gevonden — geen pull_request-context, check overgeslagen.\n";
        exit(0);
    }

    exec("git fetch origin " . escapeshellarg($base) . " --depth=50 2>&1");
    $changedFiles = explode("\n", trim(shell_exec(
        'git diff --name-only origin/' . escapeshellarg($base) . '...HEAD'
    )));

    $styleChanged     = in_array('style.css', $changedFiles, true);
    $changelogChanged = in_array('CHANGELOG.md', $changedFiles, true);

    if (!$styleChanged && !$changelogChanged) {
        echo "style.css en CHANGELOG.md zijn niet gewijzigd — check overgeslagen.\n";
        exit(0);
    }

    if ($styleChanged xor $changelogChanged) {
        $missing = $styleChanged ? 'CHANGELOG.md' : 'style.css';
        $present = $styleChanged ? 'style.css' : 'CHANGELOG.md';
        echo "FOUT: $present is gewijzigd maar $missing niet. Volgens DEVELOPMENT.md hoort een versiebump\n";
        echo "in style.css altijd samen te gaan met een changelog-sectie in CHANGELOG.md (en andersom).\n";
        exit(1);
    }

    // Beide zijn gewijzigd — versienummers moeten overeenkomen.
    $style = file_get_contents($root . '/style.css');
    if (!preg_match('/^\s*Version:\s*([\d.]+)/m', $style, $m)) {
        echo "FOUT: kon geen 'Version:' regel vinden in style.css.\n";
        exit(1);
    }
    $styleVersion = $m[1];

    $changelog = file_get_contents($root . '/CHANGELOG.md');
    $lines     = explode("\n", $changelog);
    $heading   = null;
    foreach ($lines as $line) {
        if (strpos($line, '## ') === 0) {
            $heading = $line;
            break;
        }
    }

    if ($heading === null) {
        echo "FOUT: geen '## vX.Y.Z'-kop gevonden in CHANGELOG.md.\n";
        exit(1);
    }

    if (strpos($heading, '—') !== false) {
        echo "FOUT: de bovenste sectie in CHANGELOG.md heeft al een releasedatum (\"$heading\") —\n";
        echo "dat betekent dat hij al gearchiveerd had moeten zijn. Voeg een nieuwe, ongedateerde\n";
        echo "'## v$styleVersion'-kop toe voor de huidige ontwikkelronde.\n";
        exit(1);
    }

    if (!preg_match('/^##\s*v([\d.]+)\s*$/', trim($heading), $m)) {
        echo "FOUT: kon geen versienummer uit de CHANGELOG.md-kop halen (\"$heading\").\n";
        exit(1);
    }
    $changelogVersion = $m[1];

    if ($changelogVersion !== $styleVersion) {
        echo "FOUT: style.css heeft versie $styleVersion, maar CHANGELOG.md heeft een kop voor v$changelogVersion.\n";
        exit(1);
    }

    echo "style.css en CHANGELOG.md staan beide op versie $styleVersion.\n";
    exit(0);
?>
