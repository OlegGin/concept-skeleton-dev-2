<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — Internal Server Error</title>
    <style>
        :root { --bg: #ffffff; --text: #1a1a1a; --accent: #ef4444; --muted: #6b7280; }
        body { margin: 0; font-family: system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--text); display: flex; align-items: center; justify-content: center; height: 100vh; text-align: center; }
        .container { max-width: 450px; padding: 20px; }
        .code { font-size: 120px; font-weight: 900; margin: 0; line-height: 1; color: var(--accent); opacity: 0.1; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: -1; }
        h1 { font-size: 24px; margin-bottom: 10px; font-weight: 700; }
        p { color: var(--muted); line-height: 1.6; margin-bottom: 30px; }
        .btn { display: inline-block; border: 2px solid var(--text); color: var(--text); padding: 10px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; transition: all 0.2s; }
        .btn:hover { background: var(--text); color: var(--bg); }
        svg { width: 64px; height: 64px; margin-bottom: 20px; color: var(--accent); }
        .debug-hint { margin-top: 40px; font-size: 12px; color: #ccc; text-transform: uppercase; letter-spacing: 1px; }
    </style>
</head>
<body>
<div class="code">500</div>
<div class="container">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
    </svg>
    <h1>Internal Server Error</h1>
    <p>Something went wrong on our end. Our engineers have been notified and are working on a fix. Please try again later.</p>
    <a href="/" class="btn">Return Home</a>
    <div class="debug-hint">Fallback Mode</div>
</div>
</body>
</html>
