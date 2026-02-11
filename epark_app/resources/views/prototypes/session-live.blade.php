<!doctype html>
<html lang="fr">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Session Live - Prototype</title>
    <style>
      @import url("https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600&display=swap");

      :root {
        --bg-1: #eef7f3;
        --bg-2: #dff0e9;
        --ink: #11231b;
        --muted: #5b6f67;
        --accent: #118a67;
        --accent-2: #2a7bdc;
        --accent-soft: #7fd9b8;
        --card: #ffffff;
        --surface: #f6fbf9;
        --ring-track: #d3e5df;
        --shadow: 0 16px 40px rgba(17, 35, 27, 0.12);
      }

      * {
        box-sizing: border-box;
      }

      body {
        margin: 0;
        font-family: "Space Grotesk", "Segoe UI", sans-serif;
        color: var(--ink);
        background: radial-gradient(120% 120% at 0% 0%, #fff 0%, var(--bg-1) 30%, var(--bg-2) 100%);
        min-height: 100vh;
      }

      .frame {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        padding: 20px 18px 96px;
        gap: 18px;
        position: relative;
        overflow: hidden;
      }

      .shape {
        position: absolute;
        inset: auto;
        border-radius: 999px;
        filter: blur(0.5px);
        opacity: 0.35;
        z-index: 0;
      }

      .shape.one {
        width: 220px;
        height: 220px;
        background: var(--accent-2);
        top: -70px;
        right: -60px;
      }

      .shape.two {
        width: 260px;
        height: 260px;
        background: var(--accent-soft);
        bottom: 90px;
        left: -80px;
      }

      .top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        position: relative;
        z-index: 1;
      }

      .title {
        font-family: "Fraunces", serif;
        font-size: 22px;
        letter-spacing: 0.3px;
      }

      .pill {
        padding: 6px 12px;
        background: var(--accent);
        color: #fff;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
      }

      .card {
        background: var(--card);
        border-radius: 22px;
        padding: 16px;
        box-shadow: var(--shadow);
        position: relative;
        z-index: 1;
      }

      .info {
        display: grid;
        gap: 8px;
      }

      .info-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 13px;
        color: var(--muted);
      }

      .info-row strong {
        color: var(--ink);
        font-weight: 600;
      }

      .progress {
        display: grid;
        place-items: center;
        padding: 18px 0;
      }

      .ring {
        width: 210px;
        height: 210px;
        border-radius: 50%;
        background: conic-gradient(var(--accent) 0deg, var(--accent) 240deg, var(--ring-track) 240deg 360deg);
        display: grid;
        place-items: center;
        animation: fill 6s ease-in-out infinite alternate;
      }

      .ring::before {
        content: "";
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background: var(--surface);
        box-shadow: inset 0 0 0 1px rgba(31, 27, 22, 0.08);
      }

      .ring-content {
        position: absolute;
        text-align: center;
      }

      .ring-content h2 {
        margin: 0;
        font-size: 32px;
        font-weight: 700;
        letter-spacing: 0.5px;
      }

      .ring-content span {
        display: block;
        margin-top: 4px;
        font-size: 12px;
        color: var(--muted);
      }

      .timeline {
        display: grid;
        gap: 10px;
      }

      .line {
        height: 8px;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--accent) 0%, var(--accent) 65%, var(--ring-track) 65%, var(--ring-track) 100%);
        position: relative;
      }

      .line::after {
        content: "";
        width: 18px;
        height: 18px;
        background: var(--accent-2);
        border-radius: 50%;
        position: absolute;
        left: calc(65% - 9px);
        top: -5px;
        box-shadow: 0 6px 12px rgba(244, 162, 97, 0.35);
      }

      .times {
        display: flex;
        justify-content: space-between;
        font-size: 12px;
        color: var(--muted);
      }

      .actions {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
      }

      .action {
        padding: 12px 8px;
        border-radius: 16px;
        border: 1px solid rgba(31, 27, 22, 0.08);
        background: var(--surface);
        font-size: 12px;
        text-align: center;
        font-weight: 600;
        display: grid;
        gap: 6px;
        place-items: center;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
      }

      .action.primary {
        background: var(--accent);
        color: #fff;
        border: none;
      }

      .action svg {
        width: 22px;
        height: 22px;
        display: block;
      }

      .action span {
        font-size: 11px;
        letter-spacing: 0.2px;
      }

      .action:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(31, 27, 22, 0.12);
        border-color: rgba(31, 27, 22, 0.18);
      }

      .action.primary:hover {
        box-shadow: 0 14px 28px rgba(17, 138, 103, 0.35);
      }

      .floating {
        position: fixed;
        left: 50%;
        transform: translateX(-50%);
        bottom: 78px;
        background: var(--ink);
        color: #fff;
        padding: 8px 16px;
        border-radius: 999px;
        font-size: 12px;
        box-shadow: var(--shadow);
      }

      .nav {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        padding: 12px 18px 20px;
        background: var(--surface);
        border-top: 1px solid rgba(31, 27, 22, 0.08);
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 6px;
      }

      .nav button {
        background: none;
        border: none;
        font-size: 11px;
        color: var(--muted);
      }

      .nav .active {
        color: var(--accent);
        font-weight: 700;
      }

      @keyframes fill {
        0% {
          background: conic-gradient(var(--accent) 0deg, var(--accent) 210deg, var(--ring-track) 210deg 360deg);
        }
        100% {
          background: conic-gradient(var(--accent) 0deg, var(--accent) 270deg, var(--ring-track) 270deg 360deg);
        }
      }

      @media (min-width: 540px) {
        .frame {
          max-width: 420px;
          margin: 0 auto;
        }
      }
    </style>
  </head>
  <body>
    <div class="frame">
      <div class="shape one"></div>
      <div class="shape two"></div>

      <header class="top">
        <div class="title">Session Live</div>
        <div class="pill">Auto</div>
      </header>

      <section class="card info">
        <div class="info-row">
          <span>Place</span>
          <strong>Parking B - 12A</strong>
        </div>
        <div class="info-row">
          <span>Debut</span>
          <strong>14:30</strong>
        </div>
        <div class="info-row">
          <span>Fin</span>
          <strong>16:00</strong>
        </div>
      </section>

      <section class="card progress">
        <div class="ring"></div>
        <div class="ring-content">
          <h2>45 min</h2>
          <span>restant</span>
        </div>
      </section>

      <section class="card timeline">
        <div class="line"></div>
        <div class="times">
          <span>14:30</span>
          <span>16:00</span>
        </div>
      </section>

      <section class="actions">
        <div class="action primary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 6v6l4 2" />
            <circle cx="12" cy="12" r="9" />
          </svg>
          <span>Prolonger</span>
        </div>
        <div class="action">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 21c4.5-4.5 7-7.5 7-11a7 7 0 1 0-14 0c0 3.5 2.5 6.5 7 11z" />
            <circle cx="12" cy="10" r="2.5" />
          </svg>
          <span>Aller vers</span>
        </div>
        <div class="action">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M6 6h12" />
            <path d="M8 6v12a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6" />
          </svg>
          <span>Terminer</span>
        </div>
        <div class="action">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 16v.01" />
            <path d="M12 8a3 3 0 0 1 2 5.2c-.8.7-2 1.3-2 2.3" />
          </svg>
          <span>Support</span>
        </div>
      </section>
    </div>

    <div class="floating">Session en cours - retour 1 tap</div>

    <nav class="nav">
      <button>Accueil</button>
      <button class="active">Live</button>
      <button>Places</button>
      <button>Profil</button>
    </nav>
  </body>
</html>
