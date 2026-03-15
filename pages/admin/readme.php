<?php
$pageTitle = 'How It Works';
require APP_ROOT . '/templates/admin-header.php';
?>

<div class="readme">
  <h2>Prayer Display — How It Works</h2>
  <p class="subtitle">Everything you need to know about setting up, running, and troubleshooting the prayer display.</p>

  <hr>

  <h3>What This Does</h3>
  <p>The Prayer Display shows a live, scrolling list of names on a TV or screen at church. When someone checks in to a prayer event using Planning Center (PCO), their name automatically appears on the display within 60 seconds. Families are grouped together by last name.</p>

  <div class="flow-diagram">
    <code>Someone checks in on PCO &rarr; Our system pulls the name &rarr; Name appears on the display</code>
  </div>

  <hr>

  <h3>Setting Up a New Church</h3>
  <p>Follow these steps when a church signs up for the prayer display.</p>

  <h4>What You Need From the Church</h4>
  <ul>
    <li>Their church name</li>
    <li>A short URL-friendly slug (e.g., "first-baptist" or "grace-community") &mdash; lowercase, hyphens only</li>
    <li>Their timezone (e.g., America/New_York, America/Chicago)</li>
    <li>Their PCO Check-In event ID (see below for how to find it)</li>
    <li>A background image for their display (optional &mdash; we have a default)</li>
    <li>A PCO admin who can approve the OAuth connection</li>
  </ul>

  <h4>Step-by-Step Setup</h4>
  <ol>
    <li>Log into the <a href="<?= url('/admin') ?>">admin panel</a></li>
    <li>Click <strong>"+ Add Church"</strong></li>
    <li>Fill in the church name, slug, timezone, and PCO event ID</li>
    <li>Upload their background image if they have one</li>
    <li>Save the church, then click <strong>"Authorize"</strong> next to it on the dashboard</li>
    <li>This generates a link &mdash; send it to the church's PCO admin</li>
    <li>The admin opens the link, logs into PCO, and clicks "Allow"</li>
    <li>Come back to the dashboard &mdash; token status should show <strong>"Healthy"</strong></li>
    <li>Open the display URL to verify it works: <code>bluewavecreativedesign.com/prayer/d/{slug}</code></li>
    <li>Have someone check in on PCO and confirm the name appears within 60 seconds</li>
  </ol>

  <div class="callout callout-info">
    <strong>Finding the PCO Event ID:</strong> The church admin goes to Planning Center Check-Ins, clicks on the prayer event, and looks at the URL in their browser. The number at the end is the event ID. For example: <code>check-ins/events/<strong>945124</strong></code> &mdash; the event ID is <strong>945124</strong>.
  </div>

  <h4>What to Send the Church After Setup</h4>
  <ul>
    <li>Their display URL: <code>bluewavecreativedesign.com/prayer/d/{slug}</code></li>
    <li>Tell them to open this URL in a browser on their display TV/screen</li>
    <li>Click the small icon in the bottom-right corner to go fullscreen</li>
    <li>Leave it open &mdash; it updates automatically, no manual refresh needed</li>
    <li>Names reset each day (only shows today's check-ins)</li>
  </ul>

  <hr>

  <h3>How It Stays Running</h3>
  <p>Once set up, the system runs itself. Here's what happens automatically:</p>

  <table class="info-table">
    <tr>
      <td><strong>Name updates</strong></td>
      <td>The display checks for new check-ins every 60 seconds</td>
    </tr>
    <tr>
      <td><strong>Token refresh</strong></td>
      <td>PCO access tokens expire every ~2 hours. The app automatically renews them whenever the display is being viewed. A backup cron job also refreshes tokens every hour in case nobody is viewing the display.</td>
    </tr>
    <tr>
      <td><strong>Health monitoring</strong></td>
      <td>A health check runs every 30 minutes. If anything is wrong with any church (expired token, API error, missing config), an email is sent to kenny@ and josh@bluewavecreativedesign.com.</td>
    </tr>
    <tr>
      <td><strong>Code updates</strong></td>
      <td>When we push code changes, the display pages automatically reload within 60 seconds. No one at the church needs to do anything.</td>
    </tr>
  </table>

  <hr>

  <h3>The Display</h3>
  <table class="info-table">
    <tr>
      <td><strong>URL</strong></td>
      <td><code>bluewavecreativedesign.com/prayer/d/{slug}</code></td>
    </tr>
    <tr>
      <td><strong>Fullscreen</strong></td>
      <td>Click the small icon in the bottom-right corner</td>
    </tr>
    <tr>
      <td><strong>Scrolling</strong></td>
      <td>Auto-scrolls when 6+ names are checked in. Static list for fewer.</td>
    </tr>
    <tr>
      <td><strong>No check-ins yet</strong></td>
      <td>Shows "Be the first to check in today"</td>
    </tr>
    <tr>
      <td><strong>Something broken</strong></td>
      <td>Shows "Unable to load check-ins" &mdash; you'll get an email alert</td>
    </tr>
    <tr>
      <td><strong>Background</strong></td>
      <td>Custom per church (uploaded via admin) or default image</td>
    </tr>
    <tr>
      <td><strong>Name sorting</strong></td>
      <td>Sorted by last name so families appear together</td>
    </tr>
    <tr>
      <td><strong>Testing</strong></td>
      <td>Add <code>?simulate=empty</code> or <code>?simulate=error</code> to the URL to test those states</td>
    </tr>
  </table>

  <hr>

  <h3>Troubleshooting</h3>

  <h4>A church reports names aren't showing</h4>
  <ol>
    <li>Check the <a href="<?= url('/admin') ?>">dashboard</a> &mdash; is the token status "Healthy"?</li>
    <li>If "Error" or "No token" &mdash; click <strong>Authorize</strong> and have the church admin re-approve</li>
    <li>Check that the PCO event ID is correct</li>
    <li>Check that the timezone is correct (wrong timezone = "today" calculated wrong)</li>
    <li>Open the display URL yourself to see what it shows</li>
  </ol>

  <h4>Names are showing but not updating</h4>
  <ul>
    <li>The display refreshes every 60 seconds. Wait a minute after someone checks in.</li>
    <li>If still stale after a few minutes, try a hard refresh (Cmd+Shift+R or Ctrl+Shift+R)</li>
  </ul>

  <h4>Display shows "Unable to load check-ins"</h4>
  <ul>
    <li>The PCO connection is broken. Check token status on the dashboard.</li>
    <li>If the token is "Error" &mdash; re-authorize.</li>
    <li>If PCO itself is down, wait for it to come back. The display will recover automatically.</li>
  </ul>

  <h4>You got a health alert email</h4>
  <ul>
    <li>Log into the <a href="<?= url('/admin') ?>">admin panel</a> and check which church has an issue</li>
    <li>Most common fix: click <strong>Authorize</strong> to reconnect the PCO token</li>
    <li>Alerts won't repeat for the same church for 2 hours, so you have time to fix it</li>
  </ul>

  <hr>

  <h3>Quick Reference</h3>
  <table class="info-table">
    <tr><td><strong>Admin panel</strong></td><td><code>bluewavecreativedesign.com/prayer/admin</code></td></tr>
    <tr><td><strong>Display URL pattern</strong></td><td><code>bluewavecreativedesign.com/prayer/d/{slug}</code></td></tr>
    <tr><td><strong>Health alert recipients</strong></td><td>kenny@ and josh@bluewavecreativedesign.com</td></tr>
    <tr><td><strong>Token refresh cron</strong></td><td>Every hour</td></tr>
    <tr><td><strong>Health check cron</strong></td><td>Every 30 minutes</td></tr>
    <tr><td><strong>Cron logs</strong></td><td><code>logs/cron.log</code> on the server</td></tr>
    <tr><td><strong>Code repo</strong></td><td><code>github.com/BlueWaveCreative/prayer-display</code></td></tr>
    <tr><td><strong>Server path</strong></td><td><code>public_html/prayer/</code> on bluewavecreativedesign.com</td></tr>
  </table>

  <hr>

  <h3>Technical Details</h3>
  <p>For developers working on the code.</p>

  <table class="info-table">
    <tr><td><strong>Language</strong></td><td>PHP 8.x, vanilla JS (no build step)</td></tr>
    <tr><td><strong>Database</strong></td><td>MySQL</td></tr>
    <tr><td><strong>Hosting</strong></td><td>SiteGround (Apache behind nginx proxy)</td></tr>
    <tr><td><strong>Auth</strong></td><td>Admin: email/password (bcrypt). PCO: OAuth 2.0 per church.</td></tr>
    <tr><td><strong>Deploy</strong></td><td>Push to <code>main</code> &rarr; GitHub Actions SFTP &rarr; SiteGround</td></tr>
    <tr><td><strong>Config</strong></td><td><code>src/config.php</code> &mdash; server only, never in git</td></tr>
  </table>

  <h4>Database Tables</h4>
  <ul>
    <li><code>admin_users</code> &mdash; Admin login accounts</li>
    <li><code>churches</code> &mdash; Church config (slug, name, timezone, event ID, background, active flag)</li>
    <li><code>church_tokens</code> &mdash; PCO OAuth tokens per church (access, refresh, expiry, status tracking)</li>
    <li><code>oauth_states</code> &mdash; Temporary CSRF protection for OAuth flow</li>
  </ul>

  <h4>Key Files</h4>
  <ul>
    <li><code>index.php</code> &mdash; Router (all requests go through here)</li>
    <li><code>api/checkins.php</code> &mdash; Check-in API endpoint</li>
    <li><code>src/pco-api.php</code> &mdash; Fetches and sorts names from PCO</li>
    <li><code>src/pco-oauth.php</code> &mdash; OAuth token management (auto-refresh logic)</li>
    <li><code>pages/display.php</code> &mdash; The public display page (HTML + JS)</li>
    <li><code>cron/refresh-tokens.php</code> &mdash; Hourly token refresh backup</li>
    <li><code>cron/health-check.php</code> &mdash; Health monitoring + email alerts</li>
  </ul>
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
