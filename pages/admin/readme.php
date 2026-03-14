<?php
$pageTitle = 'How It Works';
require APP_ROOT . '/templates/admin-header.php';
?>

<div class="readme">
  <h2>Prayer Display — How It Works</h2>
  <p class="subtitle">A quick reference for how everything syncs, runs, and connects.</p>

  <hr>

  <h3>Overview</h3>
  <p>The Prayer Display shows a live, scrolling list of people who have checked in to a church's prayer event in Planning Center Online (PCO). Each church gets its own display URL and connects its own PCO account via OAuth.</p>

  <div class="flow-diagram">
    <code>PCO Check-In &rarr; PCO API &rarr; Prayer Display API &rarr; Browser JS &rarr; Scrolling Names</code>
  </div>

  <hr>

  <h3>How Check-Ins Sync</h3>
  <ul>
    <li><strong>Source:</strong> Planning Center Check-Ins (the PCO app people use at church)</li>
    <li><strong>API endpoint:</strong> <code>/api/checkins?church={slug}</code></li>
    <li><strong>What it does:</strong> Fetches today's check-ins from PCO for the church's configured event ID, filtered by the church's timezone</li>
    <li><strong>Refresh rate:</strong> The display page fetches fresh data every <strong>60 seconds</strong></li>
    <li><strong>Sorting:</strong> Names are sorted by <strong>last name</strong> so families appear together</li>
    <li><strong>Deduplication:</strong> If someone checks in multiple times, they only appear once</li>
    <li><strong>Cache busting:</strong> Each API fetch includes a timestamp parameter (<code>_t=</code>) to bypass proxy caches</li>
  </ul>

  <hr>

  <h3>Display Pages</h3>
  <table class="info-table">
    <tr>
      <td><strong>Display URL</strong></td>
      <td><code>bluewavecreativedesign.com/prayer/d/{church-slug}</code></td>
    </tr>
    <tr>
      <td><strong>Fullscreen</strong></td>
      <td>Click the small icon in the bottom-right corner (designed to be nearly invisible)</td>
    </tr>
    <tr>
      <td><strong>Scrolling</strong></td>
      <td>Auto-scrolls when 6+ names are checked in. Static list for fewer names.</td>
    </tr>
    <tr>
      <td><strong>Empty state</strong></td>
      <td>Shows "Be the first to check in today" when no one has checked in yet</td>
    </tr>
    <tr>
      <td><strong>Error state</strong></td>
      <td>Shows "Unable to load check-ins" if the API fails (e.g., expired token)</td>
    </tr>
    <tr>
      <td><strong>Background</strong></td>
      <td>Custom per church (uploaded via admin) or default image</td>
    </tr>
    <tr>
      <td><strong>Testing</strong></td>
      <td>Add <code>?simulate=empty</code> or <code>?simulate=error</code> to the display URL to test those states</td>
    </tr>
  </table>

  <hr>

  <h3>PCO OAuth Tokens</h3>
  <p>Each church connects to PCO via OAuth 2.0. Tokens expire every ~2 hours.</p>
  <ul>
    <li><strong>Automatic refresh:</strong> The app refreshes tokens on-the-fly when they're within 5 minutes of expiring</li>
    <li><strong>Cron backup:</strong> A cron job runs <strong>every hour</strong> to proactively refresh tokens expiring within 30 minutes</li>
    <li><strong>Cron log:</strong> Output appends to <code>logs/cron.log</code></li>
    <li><strong>Token status:</strong> Visible on the admin dashboard — "Healthy", "Error", "No token", or "Unknown"</li>
    <li><strong>Re-authorize:</strong> If a token goes bad, click "Authorize" on the dashboard to reconnect</li>
    <li><strong>Health check:</strong> <code>/api/token-health?church={slug}</code> returns token status as JSON</li>
  </ul>
  <p><strong>If names stop showing:</strong> Check the token status first. Expired tokens return empty results with no error — this is the #1 cause of "no names showing."</p>

  <hr>

  <h3>Health Alerts</h3>
  <p>A cron job runs every <strong>30 minutes</strong> and checks each active church for problems:</p>
  <ul>
    <li>Missing or expired OAuth token</li>
    <li>API errors recorded</li>
    <li>No successful API call in 6+ hours</li>
    <li>Missing PCO event ID</li>
  </ul>
  <p>If any issues are found, an email is sent to <strong>kenny@</strong> and <strong>josh@bluewavecreativedesign.com</strong>. Alerts won't repeat for the same church within 2 hours to avoid spam.</p>
  <p>Cron log: <code>logs/cron.log</code></p>

  <hr>

  <h3>Adding a New Church</h3>
  <ol>
    <li>Log into this admin panel</li>
    <li>Click <strong>"+ Add Church"</strong></li>
    <li>Fill in: name, slug (lowercase, hyphens only), timezone, PCO event ID</li>
    <li>Optionally upload a background image</li>
    <li>Click <strong>"Authorize"</strong> — this generates a PCO OAuth link</li>
    <li>The church admin opens that link, logs into PCO, and approves access</li>
    <li>Token status should show "Healthy" after approval</li>
    <li>Share the display URL: <code>bluewavecreativedesign.com/prayer/d/{slug}</code></li>
  </ol>
  <p><strong>Finding the PCO Event ID:</strong> In Planning Center Check-Ins, go to the event. The ID is in the URL: <code>check-ins/events/<strong>945124</strong></code></p>

  <hr>

  <h3>Deployment</h3>
  <table class="info-table">
    <tr>
      <td><strong>Code repo</strong></td>
      <td><code>github.com/BlueWaveCreative/prayer-display</code></td>
    </tr>
    <tr>
      <td><strong>Auto-deploy</strong></td>
      <td>Push to <code>main</code> &rarr; GitHub Actions SFTP &rarr; SiteGround</td>
    </tr>
    <tr>
      <td><strong>Server path</strong></td>
      <td><code>public_html/prayer/</code> on bluewavecreativedesign.com</td>
    </tr>
    <tr>
      <td><strong>Config</strong></td>
      <td><code>src/config.php</code> — lives on server only, never in git</td>
    </tr>
    <tr>
      <td><strong>Excluded from deploy</strong></td>
      <td>config.php, database/, logs/, .env</td>
    </tr>
  </table>

  <div class="callout callout-info">
    <strong>Deploys take effect immediately.</strong> The app sends <code>Cache-Control: no-store</code> headers so SiteGround's nginx proxy won't cache responses. If a deploy ever seems stuck, flush the Dynamic Cache as a last resort: Site Tools &rarr; Speed &rarr; Caching &rarr; Dynamic Cache &rarr; Flush.
  </div>

  <hr>

  <h3>Auto-Refresh on Deploy</h3>
  <p>Display pages automatically reload within 60 seconds of a deploy. No manual refresh needed.</p>
  <ul>
    <li>Each deploy generates a <code>version.txt</code> from the git commit hash</li>
    <li>The API includes the version in every response</li>
    <li>The display JS detects version changes and reloads the page</li>
  </ul>

  <hr>

  <h3>Architecture</h3>
  <table class="info-table">
    <tr><td><strong>Language</strong></td><td>PHP 8.x</td></tr>
    <tr><td><strong>Database</strong></td><td>MySQL</td></tr>
    <tr><td><strong>Hosting</strong></td><td>SiteGround (Apache behind nginx proxy)</td></tr>
    <tr><td><strong>Frontend</strong></td><td>Vanilla JS, no build step</td></tr>
    <tr><td><strong>Fonts</strong></td><td>Playfair Display (headings), Poppins (names)</td></tr>
    <tr><td><strong>Auth</strong></td><td>Admin: email/password (bcrypt). PCO: OAuth 2.0 per church.</td></tr>
  </table>

  <h4>Database Tables</h4>
  <ul>
    <li><code>admin_users</code> — Admin login accounts</li>
    <li><code>churches</code> — Church config (slug, name, timezone, event ID, background image, active flag)</li>
    <li><code>church_tokens</code> — PCO OAuth tokens per church (access, refresh, expiry, last success/error)</li>
    <li><code>oauth_states</code> — CSRF protection for OAuth flow (temporary, auto-cleaned)</li>
  </ul>

  <h4>Key Files</h4>
  <ul>
    <li><code>index.php</code> — Router (all requests go through here)</li>
    <li><code>api/checkins.php</code> — Check-in API endpoint</li>
    <li><code>src/pco-api.php</code> — Fetches and sorts names from PCO</li>
    <li><code>src/pco-oauth.php</code> — OAuth token management</li>
    <li><code>pages/display.php</code> — The public display page (HTML + JS)</li>
    <li><code>cron/refresh-tokens.php</code> — Hourly token refresh</li>
    <li><code>src/config.php</code> — Database and PCO credentials (server only)</li>
  </ul>

  <hr>

  <h3>Troubleshooting</h3>

  <h4>Names not showing</h4>
  <ol>
    <li><strong>Check token status</strong> on this dashboard. If "Error" — click Authorize to reconnect.</li>
    <li><strong>Check PCO event ID</strong> — is the right event configured?</li>
    <li><strong>Check timezone</strong> — wrong timezone means "today" is calculated wrong.</li>
    <li><strong>Flush SiteGround cache</strong> — if a deploy just happened, the old response may be cached.</li>
  </ol>

  <h4>Names showing but not updating</h4>
  <ul>
    <li>The display refreshes every 60 seconds. Wait at least a minute after someone checks in.</li>
    <li>If still stale, flush SiteGround Dynamic Cache.</li>
  </ul>

  <h4>Display shows "Unable to load check-ins"</h4>
  <ul>
    <li>PCO API is down, or the OAuth token is invalid. Check token status.</li>
    <li>Check <code>logs/cron.log</code> for refresh failures.</li>
  </ul>

  <h4>Display shows "Unable to connect to server"</h4>
  <ul>
    <li>JavaScript can't reach the API. Check if the site is up.</li>
  </ul>

  <h4>After deploying code changes</h4>
  <ol>
    <li>Verify GitHub Actions deploy succeeded (check the repo's Actions tab)</li>
    <li>Changes should take effect immediately (no cache flush needed)</li>
    <li>If something seems stuck: hard refresh (Cmd+Shift+R), then check <code>x-proxy-cache</code> header in dev tools — should be <code>MISS</code></li>
    <li>Last resort: flush SiteGround Dynamic Cache (Site Tools &rarr; Speed &rarr; Caching)</li>
  </ol>

  <hr>

  <h3>URLs Reference</h3>
  <table class="info-table">
    <tr><td><strong>Admin panel</strong></td><td><code>bluewavecreativedesign.com/prayer/admin</code></td></tr>
    <tr><td><strong>Display page</strong></td><td><code>bluewavecreativedesign.com/prayer/d/{slug}</code></td></tr>
    <tr><td><strong>Check-in API</strong></td><td><code>bluewavecreativedesign.com/prayer/api/checkins?church={slug}</code></td></tr>
    <tr><td><strong>Token health</strong></td><td><code>bluewavecreativedesign.com/prayer/api/token-health?church={slug}</code></td></tr>
    <tr><td><strong>PCO OAuth apps</strong></td><td><code>api.planningcenteronline.com/oauth/applications</code></td></tr>
    <tr><td><strong>GitHub repo</strong></td><td><code>github.com/BlueWaveCreative/prayer-display</code></td></tr>
  </table>
</div>

<style>
  .readme { max-width: 800px; margin: 0 auto; line-height: 1.7; }
  .readme h2 { margin-bottom: 5px; }
  .readme .subtitle { color: #666; margin-bottom: 20px; }
  .readme h3 { margin-top: 30px; color: #333; }
  .readme h4 { margin-top: 20px; color: #555; }
  .readme hr { border: none; border-top: 1px solid #e0e0e0; margin: 30px 0; }
  .readme ul, .readme ol { padding-left: 24px; }
  .readme li { margin-bottom: 6px; }
  .readme code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-size: 0.9em; }
  .readme .flow-diagram { background: #f8f8f8; padding: 16px; text-align: center; border-radius: 6px; margin: 16px 0; font-size: 1.1em; }
  .readme .info-table { width: 100%; border-collapse: collapse; margin: 12px 0; }
  .readme .info-table td { padding: 8px 12px; border: 1px solid #e0e0e0; vertical-align: top; }
  .readme .info-table td:first-child { width: 180px; background: #fafafa; white-space: nowrap; }
  .readme .callout { padding: 14px 18px; border-radius: 6px; margin: 16px 0; }
  .readme .callout-warning { background: #fff8e1; border-left: 4px solid #ffc107; }
  .readme .callout-info { background: #e8f4fd; border-left: 4px solid #2196f3; }
</style>

<?php require APP_ROOT . '/templates/admin-footer.php'; ?>
