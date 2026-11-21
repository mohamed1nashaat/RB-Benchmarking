<x-slot:title>RB Benchmarks — Ad Performance Intelligence for MENA</x-slot>

<x-layouts.guest>

<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --bg: #0d0f16;
    --surface: #161b22;
    --elevated: #1e2535;
    --border: rgba(255,255,255,0.08);
    --border-hi: rgba(255,255,255,0.14);
    --text: #ffffff;
    --muted: rgba(255,255,255,0.55);
    --faint: rgba(255,255,255,0.28);
    --red: #EF4444;
    --red-dim: rgba(239,68,68,0.12);
    --red-glow: rgba(239,68,68,0.18);
  }
  html { scroll-behavior: smooth; }
  body { background: var(--bg); color: var(--text); font-family: 'Geist', system-ui, sans-serif; overflow-x: hidden; -webkit-font-smoothing: antialiased; }
  a { text-decoration: none; }
  img { max-width: 100%; display: block; }

  /* ── NAV ── */
  .nav {
    position: sticky; top: 0; z-index: 100;
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 clamp(1.25rem,5vw,5rem); height: 60px;
    background: rgba(13,15,22,0.8); backdrop-filter: blur(20px) saturate(160%);
    border-bottom: 1px solid var(--border);
  }
  .nav-logo img { height: 26px; width: auto; }
  .nav-links { display: flex; align-items: center; gap: 6px; }
  .nav-link { font-size: 13.5px; font-weight: 500; color: var(--muted); padding: 6px 12px; border-radius: 7px; transition: color 150ms, background 150ms; }
  .nav-link:hover { color: white; background: rgba(255,255,255,0.06); }
  .nav-actions { display: flex; align-items: center; gap: 10px; }
  .btn-ghost { font-size: 13.5px; font-weight: 500; color: var(--muted); padding: 8px 16px; border-radius: 8px; border: 1px solid transparent; transition: all 150ms; }
  .btn-ghost:hover { color: white; border-color: var(--border); background: rgba(255,255,255,0.04); }
  .btn-primary { font-size: 13.5px; font-weight: 600; color: white; padding: 8px 18px; border-radius: 8px; background: var(--red); border: 1px solid transparent; transition: all 150ms; }
  .btn-primary:hover { background: #f56565; box-shadow: 0 0 22px var(--red-glow); }
  @media (max-width: 768px) { .nav-links { display: none; } }

  /* ── HERO ── */
  .hero {
    position: relative;
    padding: clamp(4rem,9vw,8rem) clamp(1.25rem,5vw,5rem) 0;
    overflow: hidden;
    text-align: center;
  }
  .hero-bg-grid {
    position: absolute; inset: 0; pointer-events: none;
    background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.035) 1px, transparent 0);
    background-size: 28px 28px;
    mask-image: linear-gradient(to bottom, rgba(0,0,0,0.5) 0%, rgba(0,0,0,0.9) 40%, transparent 100%);
  }
  .hero-glow-l {
    position: absolute; top: -120px; left: -180px; width: 680px; height: 680px; pointer-events: none;
    background: radial-gradient(circle, rgba(239,68,68,0.09) 0%, transparent 60%);
  }
  .hero-glow-r {
    position: absolute; top: 0px; right: -200px; width: 600px; height: 600px; pointer-events: none;
    background: radial-gradient(circle, rgba(99,102,241,0.06) 0%, transparent 60%);
  }
  .hero-inner { position: relative; max-width: 860px; margin: 0 auto; }
  .badge {
    display: inline-flex; align-items: center; gap: 7px; margin-bottom: 28px;
    padding: 5px 13px; border-radius: 999px; font-size: 11.5px; font-weight: 600;
    letter-spacing: 0.1em; text-transform: uppercase;
    background: var(--red-dim); color: var(--red); border: 1px solid rgba(239,68,68,0.22);
  }
  .badge-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--red); animation: pulse-dot 2s ease-in-out infinite; }
  @keyframes pulse-dot { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.4;transform:scale(0.8)} }
  .hero-h1 {
    font-size: clamp(2.6rem,5.5vw,4.2rem); font-weight: 800; line-height: 1.07;
    letter-spacing: -0.04em; color: white; margin-bottom: 22px;
  }
  .hero-h1 .red { color: var(--red); }
  .hero-sub {
    font-size: clamp(1rem,1.8vw,1.15rem); color: var(--muted); line-height: 1.7;
    max-width: 580px; margin: 0 auto 36px;
  }
  .hero-ctas { display: flex; align-items: center; justify-content: center; gap: 12px; flex-wrap: wrap; margin-bottom: 48px; }
  .cta-big {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 13px 28px; border-radius: 10px; font-size: 15px; font-weight: 700;
    background: var(--red); color: white; transition: all 150ms;
  }
  .cta-big:hover { background: #f56565; box-shadow: 0 0 32px var(--red-glow); transform: translateY(-1px); }
  .cta-outline {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 13px 24px; border-radius: 10px; font-size: 15px; font-weight: 500;
    border: 1px solid rgba(255,255,255,0.18); color: rgba(255,255,255,0.75); transition: all 150ms;
  }
  .cta-outline:hover { border-color: rgba(255,255,255,0.35); color: white; background: rgba(255,255,255,0.04); }

  /* ── PRODUCT MOCKUP ── */
  .mockup-wrap {
    position: relative; max-width: 1100px; margin: 0 auto;
  }
  .mockup-glow {
    position: absolute; bottom: -40px; left: 50%; transform: translateX(-50%);
    width: 80%; height: 100px;
    background: radial-gradient(ellipse, rgba(239,68,68,0.2) 0%, transparent 70%);
    pointer-events: none; filter: blur(24px);
  }
  .mockup-browser {
    border-radius: 14px 14px 0 0;
    border: 1px solid rgba(255,255,255,0.12);
    border-bottom: none;
    overflow: hidden;
    background: #1a1f2e;
    box-shadow: 0 0 0 1px rgba(255,255,255,0.04), 0 40px 80px -20px rgba(0,0,0,0.8), 0 0 60px rgba(239,68,68,0.08);
  }
  .mockup-bar {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 16px; background: #141820;
    border-bottom: 1px solid rgba(255,255,255,0.07);
  }
  .m-dot { width: 10px; height: 10px; border-radius: 50%; }
  .m-dot.r { background: #ff5f57; }
  .m-dot.y { background: #febc2e; }
  .m-dot.g { background: #28c840; }
  .m-url {
    flex: 1; text-align: center; font-size: 11px; color: rgba(255,255,255,0.25);
    background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06);
    border-radius: 5px; padding: 3px 10px; max-width: 320px; margin: 0 auto;
  }
  .mockup-screen { background: #0d0f16; padding: 20px; min-height: 400px; }

  /* Fake dashboard UI inside mockup */
  .dash-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
  .dash-title { font-size: 15px; font-weight: 700; color: white; }
  .dash-tabs { display: flex; gap: 4px; }
  .dash-tab { font-size: 11px; font-weight: 600; padding: 5px 12px; border-radius: 6px; color: rgba(255,255,255,0.45); }
  .dash-tab.active { background: rgba(239,68,68,0.15); color: #ef4444; }
  .dash-kpis { display: grid; grid-template-columns: repeat(4,1fr); gap: 10px; margin-bottom: 16px; }
  .dash-kpi {
    padding: 14px; border-radius: 10px;
    background: linear-gradient(160deg,#1c2130,#161b22);
    border: 1px solid rgba(255,255,255,0.08);
    border-top-color: rgba(255,255,255,0.13);
  }
  .dash-kpi-label { font-size: 10px; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px; }
  .dash-kpi-val { font-size: 18px; font-weight: 700; color: white; letter-spacing: -0.03em; }
  .dash-kpi-change { font-size: 10px; margin-top: 4px; }
  .dash-kpi-change.up { color: #4ade80; }
  .dash-kpi-change.down { color: #f87171; }
  .dash-kpi-change.neu { color: rgba(255,255,255,0.35); }
  .dash-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  .dash-table-wrap { border-radius: 10px; border: 1px solid rgba(255,255,255,0.08); overflow: hidden; }
  .dash-table { width: 100%; border-collapse: collapse; font-size: 11px; }
  .dash-table th { background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.4); font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; padding: 8px 12px; text-align: left; }
  .dash-table td { padding: 8px 12px; border-top: 1px solid rgba(255,255,255,0.05); color: rgba(255,255,255,0.75); }
  .dash-table tr:hover td { background: rgba(255,255,255,0.02); }
  .perf-bar { height: 4px; border-radius: 2px; background: rgba(255,255,255,0.08); overflow: hidden; margin-top: 3px; }
  .perf-fill { height: 100%; border-radius: 2px; background: #ef4444; }
  .dash-chart-wrap { border-radius: 10px; border: 1px solid rgba(255,255,255,0.08); padding: 14px; }
  .chart-label { font-size: 10px; color: rgba(255,255,255,0.4); margin-bottom: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; }
  .bar-chart { display: flex; align-items: flex-end; gap: 5px; height: 80px; }
  .bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px; }
  .bar { width: 100%; border-radius: 3px 3px 0 0; transition: opacity 150ms; }
  .bar-lbl { font-size: 8px; color: rgba(255,255,255,0.3); }
  .badge-chip { display: inline-flex; align-items: center; padding: 2px 7px; border-radius: 999px; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; }
  .chip-g { background: rgba(74,222,128,0.15); color: #4ade80; }
  .chip-y { background: rgba(251,191,36,0.15); color: #fbbf24; }
  .chip-r { background: rgba(248,113,113,0.15); color: #f87171; }

  /* ── PLATFORMS BAR ── */
  .platforms-bar {
    padding: clamp(2.5rem,5vw,4rem) clamp(1.25rem,5vw,5rem);
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
    background: rgba(255,255,255,0.01);
    text-align: center;
  }
  .platforms-label { font-size: 12px; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: var(--faint); margin-bottom: 28px; }
  .platforms-logos { display: flex; align-items: center; justify-content: center; gap: clamp(24px,4vw,56px); flex-wrap: wrap; }
  .platform-logo { display: flex; flex-direction: column; align-items: center; gap: 8px; opacity: 0.4; transition: opacity 200ms; }
  .platform-logo:hover { opacity: 0.75; }
  .platform-logo svg { width: 28px; height: 28px; }
  .platform-logo span { font-size: 11px; font-weight: 600; color: var(--muted); letter-spacing: 0.04em; }

  /* ── STATS ── */
  .stats-row {
    display: flex; align-items: stretch;
    border-bottom: 1px solid var(--border);
  }
  .stat-item {
    flex: 1; text-align: center; padding: clamp(2rem,4vw,3rem) 20px;
    position: relative;
  }
  .stat-item + .stat-item::before {
    content: ''; position: absolute; left: 0; top: 20%; height: 60%; width: 1px; background: var(--border);
  }
  .stat-num { font-size: clamp(2.2rem,4vw,3rem); font-weight: 800; letter-spacing: -0.05em; color: white; line-height: 1; margin-bottom: 7px; }
  .stat-num span { color: var(--red); }
  .stat-label { font-size: 13px; color: var(--muted); }
  @media (max-width: 640px) {
    .stats-row { flex-wrap: wrap; }
    .stat-item { flex: 0 0 50%; }
    .stat-item:nth-child(3)::before, .stat-item:nth-child(4)::before { top: 0; height: 1px; width: 60%; left: 20%; }
  }

  /* ── SECTION SHARED ── */
  .section { padding: clamp(4rem,8vw,7rem) clamp(1.25rem,5vw,5rem); }
  .section-tag { font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: var(--red); margin-bottom: 14px; }
  .section-h2 { font-size: clamp(1.7rem,3vw,2.4rem); font-weight: 800; letter-spacing: -0.03em; color: white; margin-bottom: 14px; line-height: 1.15; }
  .section-sub { font-size: 16px; color: var(--muted); line-height: 1.7; max-width: 520px; }
  .section-center { text-align: center; }
  .section-center .section-sub { margin: 0 auto; }
  .divider { border: none; border-top: 1px solid var(--border); margin: 0; }

  /* ── FEATURE BENTO GRID ── */
  .bento { display: grid; grid-template-columns: repeat(12, 1fr); gap: 14px; margin-top: 56px; }
  .bento-card {
    border-radius: 16px;
    background: linear-gradient(160deg, #1c2130 0%, #161b22 100%);
    border: 1px solid var(--border); border-top-color: var(--border-hi);
    overflow: hidden; position: relative;
    transition: border-color 220ms, transform 220ms;
  }
  .bento-card:hover { border-color: rgba(255,255,255,0.16); transform: translateY(-2px); }
  .bento-card .card-body { padding: 28px 28px 24px; }
  .bento-tag { font-size: 10px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: var(--red); margin-bottom: 10px; opacity: 0.8; }
  .bento-h3 { font-size: 18px; font-weight: 700; color: white; letter-spacing: -0.02em; margin-bottom: 10px; }
  .bento-p { font-size: 14px; color: var(--muted); line-height: 1.65; }
  .bento-visual { padding: 0 20px 0; margin-top: 20px; }

  /* Grid column spans */
  .col-5 { grid-column: span 5; }
  .col-7 { grid-column: span 7; }
  .col-4 { grid-column: span 4; }
  .col-8 { grid-column: span 8; }
  .col-6 { grid-column: span 6; }
  .col-12 { grid-column: span 12; }
  @media (max-width: 900px) {
    .col-5, .col-7, .col-4, .col-8, .col-6 { grid-column: span 12; }
  }

  /* ── FAKE METRIC TABLE in bento ── */
  .metric-rows { display: flex; flex-direction: column; gap: 0; margin-top: 8px; }
  .metric-row { display: flex; align-items: center; justify-content: space-between; padding: 9px 14px; border-radius: 8px; transition: background 150ms; }
  .metric-row:hover { background: rgba(255,255,255,0.04); }
  .metric-name { font-size: 12px; color: rgba(255,255,255,0.55); font-weight: 500; }
  .metric-vals { display: flex; align-items: center; gap: 10px; }
  .m-val { font-size: 12px; font-weight: 700; }
  .m-benchmark { font-size: 11px; color: rgba(255,255,255,0.3); }
  .m-status-good { color: #4ade80; }
  .m-status-avg { color: #fbbf24; }
  .m-status-bad { color: #f87171; }

  /* Fake AI chat in bento */
  .ai-chat { display: flex; flex-direction: column; gap: 10px; padding: 0 4px; }
  .ai-msg { max-width: 85%; }
  .ai-msg.user { align-self: flex-end; }
  .ai-msg.bot { align-self: flex-start; }
  .ai-bubble { padding: 9px 13px; border-radius: 12px; font-size: 12px; line-height: 1.55; }
  .ai-bubble.user { background: rgba(239,68,68,0.18); color: rgba(255,255,255,0.85); border-radius: 12px 12px 4px 12px; }
  .ai-bubble.bot { background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.75); border-radius: 12px 12px 12px 4px; border: 1px solid rgba(255,255,255,0.08); }
  .ai-name { font-size: 10px; color: rgba(255,255,255,0.3); margin-bottom: 4px; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; }

  /* Fake report preview */
  .report-preview { background: #1a1f2e; border-radius: 10px; border: 1px solid rgba(255,255,255,0.08); padding: 16px; }
  .rp-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
  .rp-title { font-size: 12px; font-weight: 700; color: white; }
  .rp-date { font-size: 10px; color: rgba(255,255,255,0.35); }
  .rp-kpis { display: grid; grid-template-columns: repeat(3,1fr); gap: 8px; margin-bottom: 12px; }
  .rp-kpi { text-align: center; padding: 8px; background: rgba(255,255,255,0.04); border-radius: 6px; }
  .rp-kpi-val { font-size: 14px; font-weight: 700; color: white; }
  .rp-kpi-lbl { font-size: 9px; color: rgba(255,255,255,0.35); margin-top: 2px; text-transform: uppercase; letter-spacing: 0.06em; }
  .rp-bars { display: flex; flex-direction: column; gap: 6px; }
  .rp-bar-row { display: flex; align-items: center; gap: 8px; }
  .rp-bar-label { font-size: 10px; color: rgba(255,255,255,0.4); width: 55px; flex-shrink: 0; }
  .rp-bar-track { flex: 1; height: 5px; border-radius: 3px; background: rgba(255,255,255,0.07); overflow: hidden; }
  .rp-bar-fill { height: 100%; border-radius: 3px; }
  .rp-bar-val { font-size: 10px; color: rgba(255,255,255,0.45); width: 36px; text-align: right; }

  /* ── HOW IT WORKS ── */
  .steps { display: grid; grid-template-columns: repeat(3,1fr); gap: 32px; margin-top: 56px; }
  @media (max-width: 768px) { .steps { grid-template-columns: 1fr; gap: 24px; } }
  .step { position: relative; }
  .step-num {
    width: 40px; height: 40px; border-radius: 12px; margin-bottom: 20px;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; font-weight: 800; letter-spacing: -0.03em;
    background: var(--red-dim); color: var(--red); border: 1px solid rgba(239,68,68,0.22);
  }
  .step-connector {
    display: none;
  }
  @media (min-width: 769px) {
    .step:not(:last-child) .step-connector {
      display: block; position: absolute;
      top: 20px; right: -16px; width: 32px;
      border-top: 1px dashed rgba(255,255,255,0.12);
    }
  }
  .step-title { font-size: 17px; font-weight: 700; color: white; margin-bottom: 10px; letter-spacing: -0.02em; }
  .step-desc { font-size: 14px; color: var(--muted); line-height: 1.65; }

  /* ── AI SECTION ── */
  .ai-section {
    display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center;
    padding: clamp(4rem,8vw,7rem) clamp(1.25rem,5vw,5rem);
  }
  @media (max-width: 900px) { .ai-section { grid-template-columns: 1fr; gap: 40px; } }
  .ai-feature-list { display: flex; flex-direction: column; gap: 20px; margin-top: 32px; }
  .ai-feature { display: flex; gap: 14px; align-items: flex-start; }
  .ai-feature-icon {
    width: 36px; height: 36px; flex-shrink: 0; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    background: var(--red-dim); border: 1px solid rgba(239,68,68,0.18);
  }
  .ai-feature-icon svg { width: 17px; height: 17px; stroke: var(--red); }
  .ai-feature-text .ftitle { font-size: 14px; font-weight: 700; color: white; margin-bottom: 4px; }
  .ai-feature-text .fdesc { font-size: 13px; color: var(--muted); line-height: 1.6; }

  /* Big AI chat mockup */
  .ai-mockup {
    border-radius: 16px; background: var(--surface);
    border: 1px solid var(--border); border-top-color: var(--border-hi);
    overflow: hidden;
  }
  .ai-mock-header {
    padding: 14px 18px; border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 10px;
  }
  .ai-avatar { width: 28px; height: 28px; border-radius: 8px; background: var(--red-dim); border: 1px solid rgba(239,68,68,0.22); display: flex; align-items: center; justify-content: center; }
  .ai-avatar svg { width: 14px; height: 14px; stroke: var(--red); }
  .ai-mock-name { font-size: 13px; font-weight: 700; color: white; }
  .ai-mock-sub { font-size: 11px; color: rgba(255,255,255,0.35); }
  .ai-mock-body { padding: 18px; display: flex; flex-direction: column; gap: 12px; }
  .chat-msg { max-width: 85%; }
  .chat-user { align-self: flex-end; }
  .chat-bot { align-self: flex-start; }
  .chat-bubble-u { padding: 10px 14px; border-radius: 12px 12px 3px 12px; background: rgba(239,68,68,0.18); color: rgba(255,255,255,0.9); font-size: 13px; line-height: 1.55; }
  .chat-bubble-b { padding: 10px 14px; border-radius: 12px 12px 12px 3px; background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.08); color: rgba(255,255,255,0.8); font-size: 13px; line-height: 1.55; }
  .chat-bubble-b strong { color: white; }
  .thinking-dots { display: inline-flex; gap: 3px; align-items: center; }
  .thinking-dots span { width: 5px; height: 5px; border-radius: 50%; background: rgba(255,255,255,0.3); animation: blink 1.4s ease-in-out infinite both; }
  .thinking-dots span:nth-child(2) { animation-delay: 0.2s; }
  .thinking-dots span:nth-child(3) { animation-delay: 0.4s; }
  @keyframes blink { 0%,80%,100%{opacity:0.2;transform:scale(0.8)} 40%{opacity:1;transform:scale(1)} }

  /* ── CLIENT PORTAL ── */
  .portal-section {
    padding: clamp(4rem,8vw,7rem) clamp(1.25rem,5vw,5rem);
    background: rgba(255,255,255,0.015); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);
    display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center;
  }
  @media (max-width: 900px) { .portal-section { grid-template-columns: 1fr; gap: 40px; } }
  .portal-mockup {
    border-radius: 16px; background: var(--surface); border: 1px solid var(--border);
    border-top-color: var(--border-hi); overflow: hidden;
  }
  .portal-mock-bar {
    padding: 10px 16px; background: #141820; border-bottom: 1px solid rgba(255,255,255,0.06);
    display: flex; align-items: center; gap: 8px;
  }
  .portal-mock-body { padding: 18px; }
  .portal-mock-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
  .portal-brand { display: flex; align-items: center; gap: 8px; }
  .portal-brand-icon { width: 28px; height: 28px; border-radius: 7px; background: var(--red-dim); border: 1px solid rgba(239,68,68,0.2); display: flex; align-items: center; justify-content: center; }
  .portal-brand-name { font-size: 13px; font-weight: 700; color: white; }
  .portal-kpis { display: grid; grid-template-columns: repeat(3,1fr); gap: 8px; margin-bottom: 12px; }
  .portal-kpi { padding: 10px 12px; border-radius: 8px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07); }
  .pkv { font-size: 15px; font-weight: 700; color: white; letter-spacing: -0.02em; }
  .pkl { font-size: 10px; color: rgba(255,255,255,0.35); margin-top: 2px; text-transform: uppercase; letter-spacing: 0.06em; }
  .portal-chart { border-radius: 8px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); padding: 12px; }
  .mini-bars { display: flex; align-items: flex-end; gap: 4px; height: 56px; }
  .mini-bar { flex: 1; border-radius: 2px 2px 0 0; }

  /* ── TESTIMONIAL ── */
  .testimonials { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; margin-top: 56px; }
  @media (max-width: 900px) { .testimonials { grid-template-columns: 1fr; } }
  .tcard {
    padding: 28px; border-radius: 14px;
    background: var(--surface); border: 1px solid var(--border); border-top-color: var(--border-hi);
  }
  .tcard-quote { font-size: 15px; color: rgba(255,255,255,0.75); line-height: 1.7; margin-bottom: 22px; font-style: italic; }
  .tcard-author { display: flex; align-items: center; gap: 12px; }
  .tcard-avatar { width: 38px; height: 38px; border-radius: 50%; background: var(--red-dim); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700; color: var(--red); border: 1px solid rgba(239,68,68,0.2); flex-shrink: 0; }
  .tcard-name { font-size: 13px; font-weight: 700; color: white; }
  .tcard-role { font-size: 12px; color: var(--faint); margin-top: 1px; }
  .tcard-stars { display: flex; gap: 2px; margin-bottom: 16px; }
  .tcard-stars svg { width: 13px; height: 13px; fill: #fbbf24; }

  /* ── FINAL CTA ── */
  .cta-section {
    padding: clamp(5rem,9vw,8rem) clamp(1.25rem,5vw,5rem);
    text-align: center; position: relative; overflow: hidden;
  }
  .cta-section::before {
    content: ''; position: absolute; inset: 0; pointer-events: none;
    background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.03) 1px, transparent 0);
    background-size: 28px 28px;
  }
  .cta-section::after {
    content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%);
    width: 700px; height: 400px; pointer-events: none;
    background: radial-gradient(ellipse, rgba(239,68,68,0.1) 0%, transparent 65%);
  }
  .cta-inner { position: relative; z-index: 1; max-width: 640px; margin: 0 auto; }
  .cta-h2 { font-size: clamp(2rem,4vw,3rem); font-weight: 800; letter-spacing: -0.04em; color: white; margin-bottom: 18px; }
  .cta-sub { font-size: 16px; color: var(--muted); line-height: 1.65; margin-bottom: 36px; }
  .cta-pair { display: flex; align-items: center; justify-content: center; gap: 12px; flex-wrap: wrap; }
  .cta-note { font-size: 12px; color: var(--faint); margin-top: 16px; }

  /* ── FOOTER ── */
  .footer {
    border-top: 1px solid var(--border);
    padding: clamp(2.5rem,5vw,4rem) clamp(1.25rem,5vw,5rem);
  }
  .footer-top { display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr; gap: 40px; padding-bottom: 40px; border-bottom: 1px solid var(--border); }
  @media (max-width: 768px) { .footer-top { grid-template-columns: 1fr 1fr; gap: 28px; } }
  @media (max-width: 480px) { .footer-top { grid-template-columns: 1fr; } }
  .footer-brand-desc { font-size: 13px; color: var(--muted); line-height: 1.65; margin-top: 12px; max-width: 240px; }
  .footer-col-title { font-size: 12px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--faint); margin-bottom: 14px; }
  .footer-col-links { display: flex; flex-direction: column; gap: 9px; }
  .footer-col-links a { font-size: 13px; color: var(--muted); transition: color 150ms; }
  .footer-col-links a:hover { color: white; }
  .footer-bottom { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding-top: 24px; }
  .footer-copy { font-size: 12px; color: var(--faint); }
  .footer-badges { display: flex; gap: 10px; }
  .footer-badge { font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 6px; background: rgba(255,255,255,0.05); color: var(--faint); border: 1px solid var(--border); }
</style>

<!-- ═══════════════════ NAV ═══════════════════ -->
<nav class="nav">
  <a href="/" class="nav-logo">
    <img src="/logo-dark.svg" alt="RB Benchmarks">
  </a>
  <div class="nav-links">
    <a href="#features" class="nav-link">Features</a>
    <a href="#how-it-works" class="nav-link">How it works</a>
    <a href="#ai" class="nav-link">AI Tools</a>
    <a href="#portal" class="nav-link">Client Portal</a>
  </div>
  <div class="nav-actions">
    <a href="/login" class="btn-ghost">Sign In</a>
    <a href="/register" class="btn-primary">Get Started Free</a>
  </div>
</nav>

<!-- ═══════════════════ HERO ═══════════════════ -->
<section class="hero">
  <div class="hero-bg-grid"></div>
  <div class="hero-glow-l"></div>
  <div class="hero-glow-r"></div>
  <div class="hero-inner">
    <div class="badge">
      <span class="badge-dot"></span>
      Built for MENA agencies
    </div>
    <h1 class="hero-h1">
      Know if your campaigns<br>are actually <span class="red">winning</span>
    </h1>
    <p class="hero-sub">
      Compare your real ad performance against verified industry benchmarks across
      14 industries, 5 platforms, and every MENA market. No more guessing.
    </p>
    <div class="hero-ctas">
      <a href="/register" class="cta-big">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
        Start for Free
      </a>
      <a href="/login" class="cta-outline">Sign in to dashboard</a>
    </div>
  </div>

  <!-- Dashboard Mockup -->
  <div class="mockup-wrap">
    <div class="mockup-glow"></div>
    <div class="mockup-browser">
      <div class="mockup-bar">
        <div class="m-dot r"></div>
        <div class="m-dot y"></div>
        <div class="m-dot g"></div>
        <div class="m-url">rb-benchmarks.redbananas.com/benchmarks</div>
      </div>
      <div class="mockup-screen">
        <div class="dash-header">
          <div class="dash-title">Benchmark Performance</div>
          <div class="dash-tabs">
            <span class="dash-tab active">Facebook</span>
            <span class="dash-tab">Google</span>
            <span class="dash-tab">TikTok</span>
            <span class="dash-tab">LinkedIn</span>
          </div>
        </div>
        <div class="dash-kpis">
          <div class="dash-kpi">
            <div class="dash-kpi-label">CTR</div>
            <div class="dash-kpi-val">2.84%</div>
            <div class="dash-kpi-change up">↑ 0.42% vs benchmark</div>
          </div>
          <div class="dash-kpi">
            <div class="dash-kpi-label">CPC</div>
            <div class="dash-kpi-val">SAR 3.21</div>
            <div class="dash-kpi-change up">↓ SAR 0.78 vs benchmark</div>
          </div>
          <div class="dash-kpi">
            <div class="dash-kpi-label">CPL</div>
            <div class="dash-kpi-val">SAR 42.5</div>
            <div class="dash-kpi-change down">↑ SAR 4.2 vs benchmark</div>
          </div>
          <div class="dash-kpi">
            <div class="dash-kpi-label">ROAS</div>
            <div class="dash-kpi-val">4.8×</div>
            <div class="dash-kpi-change up">↑ 1.2× vs benchmark</div>
          </div>
        </div>
        <div class="dash-row">
          <div class="dash-table-wrap">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>Campaign</th>
                  <th>CTR</th>
                  <th>vs Benchmark</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>E-Commerce Summer</td>
                  <td>3.2%</td>
                  <td><span class="badge-chip chip-g">+0.8%</span></td>
                  <td><div class="perf-bar"><div class="perf-fill" style="width:82%; background:#4ade80"></div></div></td>
                </tr>
                <tr>
                  <td>Brand Awareness Q3</td>
                  <td>1.9%</td>
                  <td><span class="badge-chip chip-y">−0.1%</span></td>
                  <td><div class="perf-bar"><div class="perf-fill" style="width:58%; background:#fbbf24"></div></div></td>
                </tr>
                <tr>
                  <td>Retargeting Aug</td>
                  <td>4.1%</td>
                  <td><span class="badge-chip chip-g">+2.0%</span></td>
                  <td><div class="perf-bar"><div class="perf-fill" style="width:95%; background:#4ade80"></div></div></td>
                </tr>
                <tr>
                  <td>Lead Gen Finance</td>
                  <td>0.9%</td>
                  <td><span class="badge-chip chip-r">−1.1%</span></td>
                  <td><div class="perf-bar"><div class="perf-fill" style="width:30%; background:#f87171"></div></div></td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="dash-chart-wrap">
            <div class="chart-label">CTR vs Industry Benchmark — Aug 2025</div>
            <div class="bar-chart">
              <div class="bar-col"><div class="bar" style="height:55%;background:rgba(239,68,68,0.3)"></div><div class="bar-lbl">Apr</div></div>
              <div class="bar-col"><div class="bar" style="height:62%;background:rgba(239,68,68,0.3)"></div><div class="bar-lbl">May</div></div>
              <div class="bar-col"><div class="bar" style="height:48%;background:rgba(239,68,68,0.3)"></div><div class="bar-lbl">Jun</div></div>
              <div class="bar-col"><div class="bar" style="height:70%;background:rgba(239,68,68,0.5)"></div><div class="bar-lbl">Jul</div></div>
              <div class="bar-col"><div class="bar" style="height:82%;background:#ef4444"></div><div class="bar-lbl">Aug</div></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:10px;">
              <div style="display:flex;align-items:center;gap:4px;font-size:10px;color:rgba(255,255,255,0.35)"><div style="width:10px;height:3px;background:#ef4444;border-radius:2px"></div> Your CTR</div>
              <div style="display:flex;align-items:center;gap:4px;font-size:10px;color:rgba(255,255,255,0.35)"><div style="width:10px;height:1px;background:rgba(255,255,255,0.3)"></div> Benchmark</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════ PLATFORMS BAR ═══════════════════ -->
<div class="platforms-bar">
  <div class="platforms-label">Connects to all major ad platforms</div>
  <div class="platforms-logos">
    <div class="platform-logo">
      <svg viewBox="0 0 24 24" fill="currentColor" style="color:#1877f2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
      <span>Facebook</span>
    </div>
    <div class="platform-logo">
      <svg viewBox="0 0 24 24" fill="none" style="width:28px;height:28px"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285f4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34a853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#fbbc05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#ea4335"/></svg>
      <span>Google</span>
    </div>
    <div class="platform-logo">
      <svg viewBox="0 0 24 24" fill="currentColor" style="color:#ffffff"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.59a8.18 8.18 0 004.78 1.52V6.67a4.83 4.83 0 01-1.01.02z"/></svg>
      <span>TikTok</span>
    </div>
    <div class="platform-logo">
      <svg viewBox="0 0 24 24" fill="currentColor" style="color:#0a66c2"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
      <span>LinkedIn</span>
    </div>
    <div class="platform-logo">
      <svg viewBox="0 0 24 24" fill="currentColor" style="color:#fffc00"><path d="M12.166 24c-.892.001-1.728-.433-2.25-1.166l-1.46-2.03c-.137-.192-.348-.307-.577-.316l-2.516-.094c-1.055-.04-1.978-.708-2.328-1.698l-.876-2.397c-.083-.226-.258-.406-.483-.494l-2.374-.915C.303 14.558-.247 13.529.104 12.498l.873-2.397c.08-.228.07-.476-.026-.697L.053 7.01C-.37 6.003.09 4.843 1.067 4.384l2.326-.997c.215-.093.388-.266.477-.482l.93-2.29C5.168.469 6.237-.089 7.31.007l2.49.209c.228.02.455-.059.618-.214l1.7-1.68C12.776-2.3 14.03-2.3 14.682-1.678l1.698 1.682c.162.158.39.237.617.217l2.494-.21c1.07-.094 2.139.46 2.508 1.622l.93 2.29c.087.218.26.39.475.483l2.327.996c.974.46 1.436 1.618 1.012 2.625l-.897 2.393c-.094.22-.103.468-.023.693l.871 2.398c.35 1.03-.2 2.06-1.198 2.39l-2.373.915c-.225.088-.4.268-.483.494l-.877 2.398c-.35.991-1.272 1.659-2.327 1.698l-2.516.093c-.228.009-.44.124-.577.316l-1.46 2.03C13.893 23.568 13.058 24 12.166 24zm0-22.5c-.46 0-.895.208-1.187.565l-1.46 2.03c-.41.572-1.043.916-1.731.944l-2.516.094c-.528.02-.99.355-1.165.852l-.876 2.397c-.247.676-.77 1.213-1.447 1.48l-2.375.915c-.489.188-.737.739-.554 1.232l.874 2.397c.24.66.23 1.378-.03 2.03l-.896 2.393c-.21.514.043 1.096.556 1.327l2.326.997c.645.277 1.153.817 1.43 1.444l.93 2.29c.187.461.638.752 1.13.71l2.49-.21c.683-.057 1.363.175 1.855.636l1.7 1.68c.373.37.97.37 1.344 0l1.7-1.68c.49-.46 1.17-.692 1.852-.635l2.494.209c.492.04.943-.25 1.13-.71l.93-2.29c.277-.627.784-1.168 1.43-1.445l2.327-.996c.513-.23.765-.812.555-1.327l-.896-2.393c-.26-.652-.27-1.37-.03-2.03l.87-2.397c.183-.493-.065-1.044-.553-1.232l-2.373-.915c-.678-.267-1.2-.804-1.448-1.48l-.877-2.398c-.174-.497-.636-.83-1.164-.85l-2.516-.094c-.689-.028-1.321-.372-1.731-.944l-1.46-2.03c-.292-.357-.727-.565-1.187-.565z"/></svg>
      <span>Snapchat</span>
    </div>
  </div>
</div>

<!-- ═══════════════════ STATS ═══════════════════ -->
<div class="stats-row">
  <div class="stat-item">
    <div class="stat-num">14<span>+</span></div>
    <div class="stat-label">Industries tracked</div>
  </div>
  <div class="stat-item">
    <div class="stat-num">5<span>×</span></div>
    <div class="stat-label">Ad platforms</div>
  </div>
  <div class="stat-item">
    <div class="stat-num">7<span>+</span></div>
    <div class="stat-label">Key metrics per platform</div>
  </div>
  <div class="stat-item">
    <div class="stat-num">AI</div>
    <div class="stat-label">Arabic + English reports</div>
  </div>
</div>

<!-- ═══════════════════ FEATURES BENTO ═══════════════════ -->
<section class="section" id="features">
  <div class="section-tag">Platform features</div>
  <h2 class="section-h2">Everything your agency needs<br>to benchmark, report, and win</h2>
  <p class="section-sub">One platform. Real MENA data. Built for Arabic-speaking markets.</p>

  <div class="bento">
    <!-- Benchmark Intelligence - wide -->
    <div class="bento-card col-7">
      <div class="card-body">
        <div class="bento-tag">Core</div>
        <h3 class="bento-h3">Benchmark Intelligence</h3>
        <p class="bento-p">Compare your CTR, CPC, CPM, CPL, CPA, and ROAS against verified MENA benchmarks. See exactly where you stand against your industry.</p>
      </div>
      <div class="bento-visual">
        <div class="metric-rows">
          <div class="metric-row">
            <span class="metric-name">CTR — E-Commerce</span>
            <div class="metric-vals">
              <span class="m-val m-status-good">3.2%</span>
              <span class="m-benchmark">Benchmark: 2.4%</span>
              <span class="badge-chip chip-g">+33%</span>
            </div>
          </div>
          <div class="metric-row">
            <span class="metric-name">CPC — E-Commerce</span>
            <div class="metric-vals">
              <span class="m-val m-status-good">SAR 2.8</span>
              <span class="m-benchmark">Benchmark: SAR 3.5</span>
              <span class="badge-chip chip-g">−20%</span>
            </div>
          </div>
          <div class="metric-row">
            <span class="metric-name">CPL — Real Estate</span>
            <div class="metric-vals">
              <span class="m-val m-status-avg">SAR 48</span>
              <span class="m-benchmark">Benchmark: SAR 44</span>
              <span class="badge-chip chip-y">+9%</span>
            </div>
          </div>
          <div class="metric-row">
            <span class="metric-name">ROAS — Fashion</span>
            <div class="metric-vals">
              <span class="m-val m-status-bad">2.1×</span>
              <span class="m-benchmark">Benchmark: 3.8×</span>
              <span class="badge-chip chip-r">−45%</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- AI Report Builder - narrow -->
    <div class="bento-card col-5">
      <div class="card-body">
        <div class="bento-tag">AI Powered</div>
        <h3 class="bento-h3">AI Report Builder</h3>
        <p class="bento-p">Generate investor-ready PDF reports with AI narrative in Arabic &amp; English. Schedule weekly or monthly delivery to clients.</p>
      </div>
      <div class="bento-visual" style="padding-bottom: 20px;">
        <div class="report-preview">
          <div class="rp-header">
            <span class="rp-title">August Performance Report</span>
            <span class="rp-date">Generated by AI · Aug 31</span>
          </div>
          <div class="rp-kpis">
            <div class="rp-kpi"><div class="rp-kpi-val">3.2%</div><div class="rp-kpi-lbl">Avg CTR</div></div>
            <div class="rp-kpi"><div class="rp-kpi-val">4.6×</div><div class="rp-kpi-lbl">ROAS</div></div>
            <div class="rp-kpi"><div class="rp-kpi-val">SAR 38</div><div class="rp-kpi-lbl">Avg CPL</div></div>
          </div>
          <div class="rp-bars">
            <div class="rp-bar-row">
              <span class="rp-bar-label">Facebook</span>
              <div class="rp-bar-track"><div class="rp-bar-fill" style="width:82%;background:#ef4444"></div></div>
              <span class="rp-bar-val">82%</span>
            </div>
            <div class="rp-bar-row">
              <span class="rp-bar-label">Google</span>
              <div class="rp-bar-track"><div class="rp-bar-fill" style="width:65%;background:#4285f4"></div></div>
              <span class="rp-bar-val">65%</span>
            </div>
            <div class="rp-bar-row">
              <span class="rp-bar-label">TikTok</span>
              <div class="rp-bar-track"><div class="rp-bar-fill" style="width:41%;background:#ffffff"></div></div>
              <span class="rp-bar-val">41%</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Media Planner -->
    <div class="bento-card col-4">
      <div class="card-body">
        <div class="bento-tag">Planning</div>
        <h3 class="bento-h3">AI Media Planner</h3>
        <p class="bento-p">AI-powered budget allocation across platforms with Ramadan seasonality built in. Three scenarios: conservative, recommended, aggressive.</p>
      </div>
    </div>

    <!-- Spend Verification -->
    <div class="bento-card col-4">
      <div class="card-body">
        <div class="bento-tag">Audit</div>
        <h3 class="bento-h3">Spend Verification</h3>
        <p class="bento-p">Cross-reference reported ad spend against your own records. Catch discrepancies before they become client disputes.</p>
      </div>
    </div>

    <!-- SEO Reports -->
    <div class="bento-card col-4">
      <div class="card-body">
        <div class="bento-tag">SEO</div>
        <h3 class="bento-h3">SEO Performance</h3>
        <p class="bento-p">Track organic visibility alongside paid. Combined paid + organic dashboards give clients the full picture in one place.</p>
      </div>
    </div>
  </div>
</section>

<hr class="divider">

<!-- ═══════════════════ HOW IT WORKS ═══════════════════ -->
<section class="section" id="how-it-works" style="padding-bottom: clamp(4rem,8vw,7rem);">
  <div class="section-center">
    <div class="section-tag">Process</div>
    <h2 class="section-h2">Up and running in 3 steps</h2>
    <p class="section-sub">Connect your ad accounts once. Benchmarks update automatically every time you log in.</p>
  </div>
  <div class="steps">
    <div class="step">
      <div class="step-num">01</div>
      <div class="step-connector"></div>
      <h3 class="step-title">Connect your ad accounts</h3>
      <p class="step-desc">Link Facebook Ads, Google Ads, TikTok, LinkedIn, or Snapchat in one click. OAuth — no passwords stored, no data ever shared.</p>
    </div>
    <div class="step">
      <div class="step-num">02</div>
      <div class="step-connector"></div>
      <h3 class="step-title">See your benchmarks instantly</h3>
      <p class="step-desc">Your campaign data is matched against industry benchmarks the moment it syncs. Green means you're beating the market. Red means opportunity.</p>
    </div>
    <div class="step">
      <div class="step-num">03</div>
      <h3 class="step-title">Share AI-generated reports</h3>
      <p class="step-desc">One click generates a client-ready PDF in Arabic or English. Schedule automated delivery weekly or monthly — no manual work.</p>
    </div>
  </div>
</section>

<hr class="divider">

<!-- ═══════════════════ AI TOOLS SECTION ═══════════════════ -->
<div class="ai-section" id="ai">
  <div>
    <div class="section-tag">AI Assistant</div>
    <h2 class="section-h2">Ask anything about your campaigns</h2>
    <p class="section-sub" style="margin-bottom:0;">The built-in AI analyst reads your data and answers in plain Arabic or English. No dashboards to dig through — just ask.</p>

    <div class="ai-feature-list">
      <div class="ai-feature">
        <div class="ai-feature-icon">
          <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
        </div>
        <div class="ai-feature-text">
          <div class="ftitle">Instant campaign diagnosis</div>
          <div class="fdesc">Ask "why is my CPL so high?" and get a specific, data-backed answer — not generic advice.</div>
        </div>
      </div>
      <div class="ai-feature">
        <div class="ai-feature-icon">
          <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
        </div>
        <div class="ai-feature-text">
          <div class="ftitle">AI-written PDF reports</div>
          <div class="fdesc">Full Arabic narrative, executive summary, and benchmark comparison table auto-generated in under a minute.</div>
        </div>
      </div>
      <div class="ai-feature">
        <div class="ai-feature-icon">
          <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
        </div>
        <div class="ai-feature-text">
          <div class="ftitle">Scheduled report delivery</div>
          <div class="fdesc">Set it once. Reports go out to clients automatically — weekly, bi-weekly, or monthly.</div>
        </div>
      </div>
    </div>
  </div>

  <div class="ai-mockup">
    <div class="ai-mock-header">
      <div class="ai-avatar">
        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
      </div>
      <div>
        <div class="ai-mock-name">RB AI Analyst</div>
        <div class="ai-mock-sub">Powered by Ollama · Arabic + English</div>
      </div>
    </div>
    <div class="ai-mock-body">
      <div class="chat-msg chat-user">
        <div class="chat-bubble-u">ليش تكلفة الاكتساب ارتفعت هذا الشهر؟</div>
      </div>
      <div class="chat-msg chat-bot">
        <div class="chat-bubble-b">بناءً على بيانات حملاتك لشهر أغسطس، ارتفع <strong>CPL من 38 ريال إلى 51 ريال</strong> (+34%). السبب الرئيسي هو انخفاض <strong>CTR في حملات "ريتارجتينج" على فيسبوك</strong> من 3.1% إلى 1.8% — أقل من معيار الصناعة البالغ 2.4%.</div>
      </div>
      <div class="chat-msg chat-user">
        <div class="chat-bubble-u">What should I do to fix it?</div>
      </div>
      <div class="chat-msg chat-bot">
        <div class="chat-bubble-b">Three quick wins: <strong>1)</strong> Refresh your retargeting creative — audiences have seen the same ad 8+ times. <strong>2)</strong> Narrow the retargeting window from 180 to 30 days. <strong>3)</strong> Test a new hook in the first 3 seconds. These should recover CTR to benchmark within 2 weeks.</div>
      </div>
      <div class="chat-msg chat-bot">
        <div class="chat-bubble-b" style="padding: 10px 14px;">
          <div class="thinking-dots"><span></span><span></span><span></span></div>
        </div>
      </div>
    </div>
  </div>
</div>

<hr class="divider">

<!-- ═══════════════════ CLIENT PORTAL ═══════════════════ -->
<div class="portal-section" id="portal">
  <div class="portal-mockup">
    <div class="portal-mock-bar">
      <div class="m-dot r"></div>
      <div class="m-dot y"></div>
      <div class="m-dot g"></div>
      <div class="m-url" style="font-size:10px; color:rgba(255,255,255,0.2); background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05); border-radius:4px; padding:2px 8px; flex:1; max-width:280px; margin:0 auto; text-align:center;">client.your-agency.com/dashboard</div>
    </div>
    <div class="portal-mock-body">
      <div class="portal-mock-header">
        <div class="portal-brand">
          <div class="portal-brand-icon">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#ef4444"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21L21 12 2.25 3v7.5L15.75 12 2.25 19.5V21z"/></svg>
          </div>
          <span class="portal-brand-name">Al-Noor Real Estate</span>
        </div>
        <span style="font-size:11px;color:rgba(255,255,255,0.3);">Aug 2025 Report</span>
      </div>
      <div class="portal-kpis">
        <div class="portal-kpi"><div class="pkv">SAR 42k</div><div class="pkl">Total Spend</div></div>
        <div class="portal-kpi"><div class="pkv">284</div><div class="pkl">Leads</div></div>
        <div class="portal-kpi"><div class="pkv" style="color:#4ade80">SAR 148</div><div class="pkl">Cost/Lead</div></div>
      </div>
      <div class="portal-chart">
        <div class="chart-label" style="font-size:9px;">Monthly spend vs leads — 2025</div>
        <div class="mini-bars">
          <div class="mini-bar" style="height:45%;background:rgba(239,68,68,0.35)"></div>
          <div class="mini-bar" style="height:52%;background:rgba(239,68,68,0.35)"></div>
          <div class="mini-bar" style="height:38%;background:rgba(239,68,68,0.35)"></div>
          <div class="mini-bar" style="height:61%;background:rgba(239,68,68,0.4)"></div>
          <div class="mini-bar" style="height:72%;background:rgba(239,68,68,0.45)"></div>
          <div class="mini-bar" style="height:58%;background:rgba(239,68,68,0.4)"></div>
          <div class="mini-bar" style="height:80%;background:#ef4444"></div>
          <div class="mini-bar" style="height:95%;background:#ef4444"></div>
        </div>
      </div>
    </div>
  </div>

  <div>
    <div class="section-tag">Client Portal</div>
    <h2 class="section-h2">Give clients a view they'll actually love</h2>
    <p class="section-sub">White-labeled dashboards under your agency domain. Clients see their numbers, reports, and media plans — nothing else.</p>

    <div class="ai-feature-list">
      <div class="ai-feature">
        <div class="ai-feature-icon">
          <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
        </div>
        <div class="ai-feature-text">
          <div class="ftitle">Read-only, zero-access risk</div>
          <div class="fdesc">Clients see performance data only. No access to other clients, billing, or agency settings.</div>
        </div>
      </div>
      <div class="ai-feature">
        <div class="ai-feature-icon">
          <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42"/></svg>
        </div>
        <div class="ai-feature-text">
          <div class="ftitle">Your brand, not ours</div>
          <div class="fdesc">Upload your logo, set your colors. Clients see your agency name everywhere, not RB Benchmarks.</div>
        </div>
      </div>
      <div class="ai-feature">
        <div class="ai-feature-icon">
          <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
        </div>
        <div class="ai-feature-text">
          <div class="ftitle">Download reports anytime</div>
          <div class="fdesc">Clients access and download any report from their archive — no need to request files from you.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<hr class="divider">

<!-- ═══════════════════ TESTIMONIALS ═══════════════════ -->
<section class="section section-center">
  <div class="section-tag">Trusted by MENA agencies</div>
  <h2 class="section-h2">What agency teams say</h2>

  <div class="testimonials">
    <div class="tcard">
      <div class="tcard-stars">
        @for($i=0;$i<5;$i++)<svg viewBox="0 0 24 24"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>@endfor
      </div>
      <p class="tcard-quote">"We were spending hours every month manually benchmarking campaigns. Now it's automatic. The AI reports in Arabic are a game-changer with our Saudi clients."</p>
      <div class="tcard-author">
        <div class="tcard-avatar">S</div>
        <div>
          <div class="tcard-name">Sarah M.</div>
          <div class="tcard-role">Head of Performance · Riyadh Agency</div>
        </div>
      </div>
    </div>
    <div class="tcard">
      <div class="tcard-stars">
        @for($i=0;$i<5;$i++)<svg viewBox="0 0 24 24"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>@endfor
      </div>
      <p class="tcard-quote">"The client portal alone is worth the subscription. My clients can see everything without bombarding me with WhatsApp messages asking for updates."</p>
      <div class="tcard-author">
        <div class="tcard-avatar">K</div>
        <div>
          <div class="tcard-name">Khalid A.</div>
          <div class="tcard-role">Founder · Dubai Digital Agency</div>
        </div>
      </div>
    </div>
    <div class="tcard">
      <div class="tcard-stars">
        @for($i=0;$i<5;$i++)<svg viewBox="0 0 24 24"><path d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>@endfor
      </div>
      <p class="tcard-quote">"Finally a benchmarking tool that actually has MENA data. We stopped using generic global benchmarks that made no sense for our Saudi e-commerce clients."</p>
      <div class="tcard-author">
        <div class="tcard-avatar">N</div>
        <div>
          <div class="tcard-name">Noura R.</div>
          <div class="tcard-role">Media Director · Jeddah</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════ FINAL CTA ═══════════════════ -->
<section class="cta-section">
  <div class="cta-inner">
    <h2 class="cta-h2">Start benchmarking your campaigns today</h2>
    <p class="cta-sub">Join MENA agencies who benchmark smarter. No credit card required to start.</p>
    <div class="cta-pair">
      <a href="/register" class="cta-big">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
        Get Started Free
      </a>
      <a href="/login" class="cta-outline">Sign in to dashboard</a>
    </div>
    <div class="cta-note">No credit card · Setup in 5 minutes · Cancel anytime</div>
  </div>
</section>

<!-- ═══════════════════ FOOTER ═══════════════════ -->
<footer class="footer">
  <div class="footer-top">
    <div>
      <img src="/logo-dark.svg" alt="RB Benchmarks" style="height:24px;">
      <p class="footer-brand-desc">Ad performance intelligence built for MENA agencies. Benchmark smarter, report faster, win more clients.</p>
    </div>
    <div>
      <div class="footer-col-title">Platform</div>
      <div class="footer-col-links">
        <a href="#features">Benchmarks</a>
        <a href="#ai">AI Reports</a>
        <a href="#portal">Client Portal</a>
        <a href="/login">Media Planner</a>
      </div>
    </div>
    <div>
      <div class="footer-col-title">Resources</div>
      <div class="footer-col-links">
        <a href="/login">Dashboard</a>
        <a href="/register">Get Started</a>
        <a href="/global-benchmarks">Global Benchmarks</a>
      </div>
    </div>
    <div>
      <div class="footer-col-title">Legal</div>
      <div class="footer-col-links">
        <a href="/privacy-policy">Privacy Policy</a>
        <a href="/terms-of-service">Terms of Service</a>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="footer-copy">© {{ date('Y') }} Red Bananas Digital. All rights reserved.</div>
    <div class="footer-badges">
      <span class="footer-badge">🇸🇦 KSA</span>
      <span class="footer-badge">🇦🇪 UAE</span>
      <span class="footer-badge">Arabic + English</span>
    </div>
  </div>
</footer>

</x-layouts.guest>
