<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mappy. My city. My sound.</title>

<link rel="stylesheet" href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div id="map"></div>

<div id="intro-overlay" class="intro-overlay">
  <div class="intro-content">
    <span class="brand-mark intro-mark" aria-hidden="true">
     <img src="./assets/icon/mainIcon.png" alt="" width="26" height="26">
    </span>
    <h1 class="intro-title">MAPPY</h1>
    <p class="intro-tagline">MY CITY. MY SOUND.</p>
    <p class="intro-text">
    An interactive urban soundscape archive dedicated to documenting the active presence of displaced and immigrant communities in Athens.   
  <br><br>
The city is shaped by the voices and memories of the people who live in it. Through collective acoustic mapping, this platform captures cultural rhythms and daily sounds within the city. By giving permanence to the ephemeral, we aim to challenge dominant narratives and advocate for marginalized communities' right to the city. Every uploaded sound serves as a digital marker of presence and a statement of existence-resistance. This platform is a tool for you to document your own experiences and mark your active presence in the city.   
    </p>

    <p class="intro-text2">

What makes you feel  happy and safe in Athens?
 Share sounds of your daily life, sounds of your habits, your language, your music, your celebrations/traditions, or the neighborhood!
<br><br>
Help build a living archive that proves you are here, you belong, and you create your own reality!   
 </p>
    <p class="intro-cta">Tap anywhere to begin</p>
  </div>
</div>

<header class="topbar">
  <div class="brand">
    <span class="brand-mark" aria-hidden="true">
      <img src="./assets/icon/mainIcon.png" alt="" width="26" height="26">
    </span>
    <div>
      <h1>MAPPY</h1>
      <p class="tagline">MY CITY. MY SOUND.</p>
    </div>
  </div>
  <div class="topbar-right">
    <div class="feedback-box">
      <a href="feedback.php">Feedback <span class="feedback-sep">|</span> Reports</a>
    </div>
    <div class="stats">
      <span id="pin-count">0</span>
      <label>sounds charted</label>
    </div>
  </div>
</header>

<button id="add-mode-btn" class="fab" type="button">
  <span class="fab-dot" aria-hidden="true"></span>
  <span class="fab-label">PLACE A SOUND</span>
</button>

<div id="hint-banner" class="hint-banner hidden">
  Tap anywhere on the map to drop your pin — <button id="cancel-mode" type="button">cancel</button>
</div>

<!-- Upload modal -->
<div id="upload-modal" class="modal-overlay hidden">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <button class="modal-close" id="modal-close" type="button" aria-label="Close">&times;</button>
    <p class="modal-eyebrow">NEW RECORDING · <span id="modal-coords">0.0000, 0.0000</span></p>
    <h2 id="modal-title">WHAT IS YOUR SOUND?</h2>

    <form id="upload-form">
      <input type="hidden" name="lat" id="field-lat">
      <input type="hidden" name="lng" id="field-lng">

      <label class="field">
        <span>Title</span>
        <input type="text" name="title" id="field-title" maxlength="80" placeholder="e.g. Music in my shop/people talking/drinking tea" required>
      </label>

      <label class="field">
        <span>Description <em>(optional)</em></span>
        <textarea name="description" id="field-description" maxlength="300" rows="2" placeholder="Where, when, what — a little context."></textarea>
      </label>

      <label class="field">
        <span>Your name <em>(optional)</em></span>
        <input type="text" name="author" id="field-author" maxlength="40" placeholder="Anonymous">
      </label>

      <label class="field file-field" id="file-drop">
        <span>Sound file</span>
        <input type="file" name="sound" id="field-sound" accept="audio/mpeg,audio/mp3,audio/wav,audio/ogg,audio/mp4,audio/x-m4a,audio/aac,audio/webm,video/mp4,.mp3,.wav,.ogg,.m4a,.webm" required>
        <span class="file-hint" id="file-hint">mp3, wav, ogg, m4a, or webm · up to 15MB</span>
      </label>

      <div class="form-error hidden" id="form-error"></div>

      <button type="submit" class="submit-btn" id="submit-btn">
        <span class="submit-label">Drop pin &amp; upload</span>
      </button>
    </form>
  </div>
</div>

<!-- Player modal (opens when clicking an existing pin) -->
<div id="player-modal" class="modal-overlay hidden">
  <div class="modal player-modal" role="dialog" aria-modal="true">
    <button class="modal-close" id="player-close" type="button" aria-label="Close">&times;</button>
    <p class="modal-eyebrow" id="player-coords">0.0000, 0.0000</p>
    <h2 id="player-title">Untitled</h2>
    <p class="player-meta" id="player-meta"></p>
    <p class="player-description" id="player-description"></p>
    <audio id="player-audio" controls preload="none"></audio>
    <button class="delete-btn hidden" id="player-delete" type="button">Remove this pin</button>
  </div>
</div>

<div id="toast" class="toast hidden"></div>

<script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>