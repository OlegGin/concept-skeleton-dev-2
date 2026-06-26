<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found</title>
    <style>
        :root { --bg: #ffffff; --text: #1a1a1a; --accent: #3b82f6; --muted: #6b7280; }
        body { margin: 0; font-family: system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--text); display: flex; align-items: center; justify-content: center; height: 100vh; text-align: center; }
        .container { max-width: 400px; padding: 20px; }
        .code { font-size: 120px; font-weight: 900; margin: 0; line-height: 1; color: var(--accent); opacity: 0.2; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: -1; }
        h1 { font-size: 24px; margin-bottom: 10px; font-weight: 700; }
        p { color: var(--muted); line-height: 1.6; margin-bottom: 30px; }
        .btn { display: inline-block; background: var(--text); color: var(--bg); padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; transition: opacity 0.2s; }
        .btn:hover { opacity: 0.8; }
        svg { width: 64px; height: 64px; margin-bottom: 20px; color: var(--accent); }
        .debug-hint { margin-top: 40px; font-size: 12px; color: #ccc; text-transform: uppercase; letter-spacing: 1px; }
    </style>
</head>
<body>
<div class="code">404</div>
<div class="container">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
    </svg>
    <h1>Page Not Found</h1>
    <p>Sorry, we couldn't find what you're looking for. The page might have been moved or deleted.</p>
    <a href="/" class="btn">Back to Home</a>
    <div class="debug-hint">Fallback Mode</div>
</div>
</body>
</html>