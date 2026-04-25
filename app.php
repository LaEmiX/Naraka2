<?php

declare(strict_types=1);

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Naraka</title>
    <link rel="stylesheet" href="/themes/auth.css">
    <link rel="stylesheet" href="/themes/app.css?v=audio-clean-06">
</head>
<body class="naraka-shell-body">

<div class="naraka-shell">

    <audio id="narakaMusic" src="/themes/audio/naraka_loop.mp3" loop preload="auto"></audio>

    <div class="naraka-start-overlay" id="narakaStartOverlay">

        <div class="auth-logo-wrap naraka-start-logo-wrap">
            <img src="/themes/images/titolo.png" alt="Naraka" class="auth-logo">
        </div>

        <div class="auth-panel naraka-start-panel">

            <p class="naraka-start-text">
                Naraka è un GdR PbC che fonde horror, fantasy e cultura pop anni ’80/’90, ideato e creato da
            </p>

            <div class="naraka-sinister-wrap">
                <img src="/themes/images/sinister.png" alt="Sinister" class="naraka-sinister-logo">
            </div>

            <p class="naraka-start-text">
                Se ami atmosfere sospese tra Stranger Things e Steven Spielberg, gli incubi di It, l’oscurità di Dark Souls e Bloodborne, fino al techno-delirio di The Lawnmower Man, WarGames e TRON, allora hai trovato il posto giusto.
            </p>

            <p class="auth-subtitle naraka-loading-text" id="narakaLoadingText"></p>

            <button type="button" class="auth-button" id="narakaStartButton">
                Avvia Echo System
            </button>

        </div>
    </div>

    <iframe
        src="about:blank"
        class="naraka-frame"
        id="narakaFrame"
        name="narakaFrame"
        title="Naraka"
        allow="autoplay"
    ></iframe>

</div>

<script>
'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const target = document.getElementById('narakaLoadingText');

    if (!target) {
        return;
    }

    const text = 'LOADING ECHO SYSTEM...';
    let i = 0;

    function typeLoadingText() {
        target.textContent = text.substring(0, i);
        i++;

        if (i <= text.length) {
            window.setTimeout(typeLoadingText, 60);
            return;
        }

        window.setTimeout(function () {
            i = 0;
            typeLoadingText();
        }, 1200);
    }

    typeLoadingText();
});
</script>

<script src="/themes/app.js?v=audio-clean-06"></script>

</body>
</html>

<?php
// by LaEmiX