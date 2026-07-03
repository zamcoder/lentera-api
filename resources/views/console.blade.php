<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg+xml" href="/lentera.svg" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    {{-- Fonts konsol: Newsreader (judul) · DM Sans (teks) · Spline Sans Mono (mono) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400;6..72,500;6..72,600&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Spline+Sans+Mono:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
    <title>Lentera — Konsol Moderasi</title>
    @viteReactRefresh
    @vite('resources/js/console/main.jsx')
  </head>
  <body>
    <div id="root"></div>
  </body>
</html>
