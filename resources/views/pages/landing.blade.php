<x-slot:title>RB Benchmarks — Ad Performance Intelligence for MENA</x-slot>

<x-layouts.guest>

<style>
  * { box-sizing: border-box; }
  :root {
    --bg: #0d0f16;
    --surface: #161b22;
    --elevated: #1e2430;
    --border: rgba(255,255,255,0.08);
    --border-top: rgba(255,255,255,0.14);
    --text: #ffffff;
    --muted: rgba(255,255,255,0.55);
    --faint: rgba(255,255,255,0.3);
    --red: #EF4444;
    --red-dim: rgba(239,68,68,0.15);
  }
  body { background: var(--bg); color: var(--text); font-family: 'Geist', system-ui, sans-serif; }

  /* Nav */
  .lp-nav {
    position: sticky; top: 0; z-index: 50;
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 clamp(1.5rem, 5vw, 5rem);
    height: 60px;
    background: rgba(13,15,22,0.85);
    backdrop-filter: blur(16px);
    border-bottom: 1px solid var(--border);
  }
  .lp-nav-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
  .lp-nav-logo img { height: 28px; width: auto; }
  .lp-nav-actions { display: flex; align-items: center; gap: 12px; }
  .lp-btn-outline {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 18px; border-radius: 8px; font-size: 14px; font-weight: 500;
    border: 1px solid rgba(255,255,255,0.2); color: rgba(255,255,255,0.8);
    text-decoration: none; transition: all 150ms;
  }
  .lp-btn-outline:hover { border-color: rgba(255,255,255,0.4); color: white; background: rgba(255,255,255,0.05); }
  .lp-btn-red {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 18px; border-radius: 8px; font-size: 14px; font-weight: 600;
    background: var(--red); color: white; text-decoration: none;
    border: 1px solid transparent; transition: all 150ms;
  }
  .lp-btn-red:hover { background: #f56565; box-shadow: 0 0 20px rgba(239,68,68,0.3); transform: scale(1.01); }

  /* Hero */
  .lp-hero {
    position: relative;
    padding: clamp(5rem, 10vw, 9rem) clamp(1.5rem, 5vw, 5rem) clamp(4rem, 8vw, 7rem);
    overflow: hidden;
  }
  .lp-hero::before {
    content: '';
    position: absolute; inset: 0;
    background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.04) 1px, transparent 0);
    background-size: 32px 32px;
    mask-image: linear-gradient(to bottom, transparent, rgba(0,0,0,0.6) 30%, rgba(0,0,0,0.6) 70%, transparent);
    pointer-events: none;
  }
  .lp-hero::after {
    content: '';
    position: absolute; top: -200px; left: -100px;
    width: 700px; height: 700px;
    background: radial-gradient(circle, rgba(239,68,68,0.07) 0%, transparent 65%);
    pointer-events: none;
  }
  .lp-hero-inner { position: relative; max-width: 820px; }
  .lp-badge {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 5px 12px; border-radius: 999px; margin-bottom: 28px;
    font-size: 12px; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase;
    background: var(--red-dim); color: var(--red);
    border: 1px solid rgba(239,68,68,0.25);
  }
  .lp-badge-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--red); animation: pulse 2s ease-in-out infinite; }
  @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }
  .lp-h1 {
    font-size: clamp(2.8rem, 6vw, 4.5rem);
    font-weight: 700; line-height: 1.08; letter-spacing: -0.03em;
    margin: 0 0 24px; color: white;
  }
  .lp-h1 .accent { color: var(--red); }
  .lp-sub {
    font-size: clamp(1rem, 2vw, 1.2rem); line-height: 1.65;
    color: var(--muted); max-width: 580px; margin: 0 0 40px;
  }
  .lp-ctas { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
  .lp-cta-primary {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 13px 26px; border-radius: 10px; font-size: 15px; font-weight: 600;
    background: var(--red); color: white; text-decoration: none;
    border: 1px solid transparent; transition: all 150ms;
  }
  .lp-cta-primary:hover { background: #f56565; box-shadow: 0 0 28px rgba(239,68,68,0.35); transform: scale(1.01); }
  .lp-cta-secondary {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 13px 26px; border-radius: 10px; font-size: 15px; font-weight: 500;
    border: 1px solid rgba(255,255,255,0.18); color: rgba(255,255,255,0.75);
    text-decoration: none; transition: all 150ms;
  }
  .lp-cta-secondary:hover { border-color: rgba(255,255,255,0.35); color: white; background: rgba(255,255,255,0.04); }

  /* Stats bar */
  .lp-stats {
    display: flex; align-items: center; gap: 0;
    border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);
    margin: 0; padding: 0 clamp(1.5rem, 5vw, 5rem);
    background: rgba(255,255,255,0.02);
  }
  .lp-stat {
    flex: 1; display: flex; flex-direction: column; align-items: center;
    padding: 28px 20px; position: relative; text-align: center;
  }
  .lp-stat + .lp-stat::before {
    content: ''; position: absolute; left: 0; top: 25%; height: 50%;
    width: 1px; background: var(--border);
  }
  .lp-stat-num {
    font-size: clamp(2rem, 4vw, 2.8rem); font-weight: 700; letter-spacing: -0.04em;
    color: white; line-height: 1; margin-bottom: 6px;
  }
  .lp-stat-num .dot { color: var(--red); font-size: 0.6em; vertical-align: middle; margin: 0 4px; }
  .lp-stat-label { font-size: 13px; color: var(--muted); letter-spacing: 0.02em; }

  /* Features */
  .lp-features {
    padding: clamp(4rem, 8vw, 7rem) clamp(1.5rem, 5vw, 5rem);
  }
  .lp-section-label {
    font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;
    color: var(--red); margin-bottom: 14px;
  }
  .lp-section-title {
    font-size: clamp(1.6rem, 3vw, 2.2rem); font-weight: 700; letter-spacing: -0.025em;
    color: white; margin: 0 0 14px;
  }
  .lp-section-sub {
    font-size: 16px; color: var(--muted); max-width: 520px; line-height: 1.6; margin: 0 0 56px;
  }
  .lp-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px;
  }
  .lp-card {
    padding: 28px 26px; border-radius: 14px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-top-color: var(--border-top);
    transition: border-color 200ms, transform 200ms;
    text-decoration: none; display: block;
  }
  .lp-card:hover { border-color: rgba(255,255,255,0.18); transform: translateY(-2px); }
  .lp-card-icon {
    width: 42px; height: 42px; border-radius: 10px; margin-bottom: 20px;
    display: flex; align-items: center; justify-content: center;
    background: var(--red-dim); border: 1px solid rgba(239,68,68,0.2);
  }
  .lp-card-icon svg { width: 20px; height: 20px; stroke: var(--red); }
  .lp-card-title { font-size: 16px; font-weight: 600; color: white; margin: 0 0 10px; letter-spacing: -0.01em; }
  .lp-card-desc { font-size: 14px; color: var(--muted); line-height: 1.65; margin: 0; }

  /* Footer */
  .lp-footer {
    border-top: 1px solid var(--border);
    padding: 28px clamp(1.5rem, 5vw, 5rem);
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;
  }
  .lp-footer-links { display: flex; align-items: center; gap: 20px; }
  .lp-footer-links a { font-size: 13px; color: var(--faint); text-decoration: none; transition: color 150ms; }
  .lp-footer-links a:hover { color: rgba(255,255,255,0.7); }
  .lp-footer-copy { font-size: 12px; color: var(--faint); }

  @media (max-width: 640px) {
    .lp-stats { flex-direction: column; }
    .lp-stat + .lp-stat::before { left: 15%; top: 0; width: 70%; height: 1px; }
  }
</style>

<!-- Nav -->
<nav class="lp-nav">
  <a href="/" class="lp-nav-logo">
    <img src="/logo.svg" alt="RB Benchmarks">
  </a>
  <div class="lp-nav-actions">
    <a href="/login" class="lp-btn-outline">Sign In</a>
    <a href="/register" class="lp-btn-red">Get Started</a>
  </div>
</nav>

<!-- Hero -->
<section class="lp-hero">
  <div class="lp-hero-inner">
    <div class="lp-badge">
      <span class="lp-badge-dot"></span>
      Built for MENA
    </div>
    <h1 class="lp-h1">
      The Benchmark Platform<br>Built for <span class="accent">MENA</span> Agencies
    </h1>
    <p class="lp-sub">
      Stop guessing. Know exactly how your campaigns perform vs. industry standards
      across every platform, industry, and funnel stage.
    </p>
    <div class="lp-ctas">
      <a href="/register" class="lp-cta-primary">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
        Get Started Free
      </a>
      <a href="/login" class="lp-cta-secondary">Sign In to Dashboard</a>
    </div>
  </div>
</section>

<!-- Stats bar -->
<div class="lp-stats">
  <div class="lp-stat">
    <div class="lp-stat-num">14<span class="dot">·</span></div>
    <div class="lp-stat-label">Industries Tracked</div>
  </div>
  <div class="lp-stat">
    <div class="lp-stat-num">5<span class="dot">·</span></div>
    <div class="lp-stat-label">Ad Platforms</div>
  </div>
  <div class="lp-stat">
    <div class="lp-stat-num" style="font-size: clamp(1.4rem, 3vw, 2rem);">AI<span class="dot">·</span></div>
    <div class="lp-stat-label">Powered Reports</div>
  </div>
</div>

<!-- Features -->
<section class="lp-features">
  <div class="lp-section-label">What you get</div>
  <h2 class="lp-section-title">Everything your agency needs to win</h2>
  <p class="lp-section-sub">One platform. Real MENA data. AI that speaks Arabic.</p>

  <div class="lp-grid">
    <div class="lp-card">
      <div class="lp-card-icon">
        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
      </div>
      <h3 class="lp-card-title">Benchmark Intelligence</h3>
      <p class="lp-card-desc">Compare your CTR, CPC, CPL, and ROAS against verified MENA benchmarks across 14 industries and 5 ad platforms.</p>
    </div>

    <div class="lp-card">
      <div class="lp-card-icon">
        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
      </div>
      <h3 class="lp-card-title">AI Report Builder</h3>
      <p class="lp-card-desc">Generate investor-ready PDF reports with AI narrative in Arabic &amp; English in minutes. Schedule weekly or monthly delivery.</p>
    </div>

    <div class="lp-card">
      <div class="lp-card-icon">
        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
      </div>
      <h3 class="lp-card-title">AI Media Planner</h3>
      <p class="lp-card-desc">AI-powered budget allocation across platforms with Ramadan seasonality built in. Three scenario options — conservative, recommended, aggressive.</p>
    </div>

    <div class="lp-card">
      <div class="lp-card-icon">
        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
      </div>
      <h3 class="lp-card-title">Client Portal</h3>
      <p class="lp-card-desc">Give clients read-only access to their own dashboards, reports, and media plans. White-labeled with your agency branding.</p>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="lp-footer">
  <a href="/" class="lp-nav-logo" style="opacity:0.6;">
    <img src="/logo.svg" alt="RB Benchmarks" style="height:22px;">
  </a>
  <div class="lp-footer-links">
    <a href="/privacy-policy">Privacy Policy</a>
    <a href="/terms-of-service">Terms of Service</a>
    <a href="/login">Sign In</a>
  </div>
  <div class="lp-footer-copy">© {{ date('Y') }} Red Bananas. All rights reserved.</div>
</footer>

</x-layouts.guest>
