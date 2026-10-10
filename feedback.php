<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Feedback &amp; Reports · Mappy</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<style>
  body.feedback-page {
    height: auto;
    min-height: 90vh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
  }
  .feedback-page-wrap {
    flex: 1 0 auto;
    width: 100%;
    max-width: 640px;
    margin: 0 auto;
    padding: 56px 24px 48px;
    box-sizing: border-box;
  }
    .feedback-footer {
    flex-shrink: 0;
    width: 100%;
    max-width: 640px;
    margin: 0 auto;
    padding: 20px 24px 32px;
    box-sizing: border-box;
    border-top: 1px solid var(--line);
    text-align: center;
    font-family: var(--font-mono);
    font-size: 13px;
    letter-spacing: 0.04em;
    color: var(--muted);
  }
  .feedback-footer p {
    margin: 0;
    font-size: 11px;
    line-height: 1.6;
    color: var(--muted);
  }
  .feedback-footer a {
    color: var(--muted);
    text-decoration: underline;
    text-underline-offset: 3px;
  }
  .feedback-footer a:hover { color: var(--amber); }
  .feedback-back {
    display: inline-block;
    font-family: var(--font-mono);
    font-size: 11px;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--muted);
    text-decoration: none;
    margin-bottom: 28px;
  }
  .feedback-back:hover { color: var(--amber); }
  .feedback-page-wrap h1 {
    font-size: 28px;
    margin: 0 0 6px;
    color: #C35E60;
  }
  .feedback-page-wrap .feedback-tagline {
    font-family: var(--font-mono);
    font-size: 12px;
    color: var(--muted);
    letter-spacing: 0.03em;
    margin: 0 0 32px;
  }
  .feedback-page-wrap h2 {
    font-size: 16px;
    color: #C35E60;
    margin: 36px 0 10px;
  }
  .feedback-page-wrap p {
    font-size: 14.5px;
    line-height: 1.6;
    color: rgba(143, 157, 170, 1);
    margin: 0 0 14px;
  }
  .feedback-page-wrap a.inline-link {
    color: var(--text);
  }
</style>
</head>
<body class="feedback-page">

<div class="feedback-page-wrap">
  <a href="index.php" class="feedback-back">&larr; Back to the map</a>

  <h1>Feedback &amp; Reports</h1>
  <p class="feedback-tagline">HELP US KEEP MAPPY HONEST AND SAFE</p>

  <h2>Feedback</h2>
  <p>
    Tell us what's working, what isn't, or what you'd like to see next. Mappy is a living project shaped
    by the people who use it.
  </p>

  <h2>Reports</h2>
  <p>
    If a pin contains something harmful, inappropriate, or that shouldn't be on the map, let us know
    here and we'll review it.
  </p>

  <h2>Contact</h2>
  <p>
    mymappy@proton.me
  </p>
</div>

<footer class="feedback-footer">
<p>
  &#x1F12F; Mappy is free open source, code created with help of AI &middot;
  <a href="https://github.com/pie223/mappy/" target="_blank" rel="noopener noreferrer">View the code on GitHub</a>
</p>
</footer>

</body>
</html>
