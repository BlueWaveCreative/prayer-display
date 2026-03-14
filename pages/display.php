<?php
$slug = $_GET['slug'] ?? '';
$db = getDb();
$stmt = $db->prepare('SELECT name, slug, background_image, is_active FROM churches WHERE slug = ?');
$stmt->execute([$slug]);
$church = $stmt->fetch();

if (!$church || !$church['is_active']) {
    http_response_code(404);
    echo 'Display not found';
    exit;
}

$bgImage = $church['background_image']
    ? url('/uploads/' . h($church['background_image']))
    : url('/images/default-background.png');
$churchName = h($church['name']);
$churchSlug = h($church['slug']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Prayer Check-in — <?= $churchName ?></title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Poppins:wght@400;500&display=swap');

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Playfair Display', Georgia, serif;
      background: url('<?= $bgImage ?>') no-repeat center top fixed;
      background-size: cover;
      color: #ffffff;
      height: 100vh;
      overflow: hidden;
    }

    .container {
      height: 100vh;
      display: flex;
      flex-direction: column;
      padding: 60px 80px;
    }

    .scroll-container {
      position: absolute;
      top: 240px;
      left: 45%;
      bottom: 60px;
      width: 50%;
      overflow: hidden;
    }

    .names-wrapper {
      animation: scroll linear infinite;
    }

    .names-list {
      list-style: none;
      text-align: center;
    }

    .names-list li {
      font-family: 'Poppins', sans-serif;
      font-size: 2.2rem;
      font-weight: 400;
      padding: 20px 0;
      color: #f3eee1;
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: rgba(255, 255, 255, 0.7);
      font-size: 1.5rem;
      font-style: italic;
    }

    .error-state {
      text-align: center;
      padding: 40px 20px;
      color: #ff6b6b;
      font-size: 1.3rem;
    }

    @keyframes scroll {
      0% { transform: translateY(0); }
      100% { transform: translateY(-50%); }
    }

    .no-scroll .names-wrapper { animation: none; }

    .fullscreen-btn {
      position: fixed;
      bottom: 10px;
      right: 10px;
      background: none;
      border: none;
      color: rgba(255, 255, 255, 0.08);
      font-size: 1.2rem;
      padding: 6px;
      cursor: pointer;
      z-index: 100;
      transition: color 0.3s;
      line-height: 1;
    }

    .fullscreen-btn:hover { color: rgba(255, 255, 255, 0.4); }
  </style>
</head>
<body>
  <button class="fullscreen-btn" id="fullscreen-btn" title="Toggle fullscreen">&#x26F6;</button>
  <div class="container">
    <div class="scroll-container" id="scroll-container">
      <div class="names-wrapper" id="names-wrapper">
        <ul class="names-list" id="names-list">
          <li>Loading...</li>
        </ul>
      </div>
    </div>
  </div>

  <script>
    var REFRESH_INTERVAL = 60000; // 1 minute
    var SCROLL_SPEED = 30;        // pixels per second
    var CHURCH_SLUG = '<?= $churchSlug ?>';

    function ensureStructure() {
      var container = document.getElementById('scroll-container');
      var wrapper = document.getElementById('names-wrapper');
      if (!wrapper) {
        container.innerHTML = '<div class="names-wrapper" id="names-wrapper"><ul class="names-list" id="names-list"></ul></div>';
      }
    }

    async function fetchCheckIns() {
      try {
        var pageParams = new URLSearchParams(window.location.search);
        var apiUrl = '<?= url('/api/checkins') ?>?church=' + CHURCH_SLUG;
        if (pageParams.has('simulate')) {
          apiUrl += '&simulate=' + pageParams.get('simulate');
        }

        apiUrl += '&_t=' + Date.now();
        var response = await fetch(apiUrl);
        var data = await response.json();
        var container = document.getElementById('scroll-container');

        if (!data.success) {
          ensureStructure();
          var wrapper = document.getElementById('names-wrapper');
          wrapper.innerHTML = '<div class="error-state">Unable to load check-ins</div>';
          wrapper.style.animation = 'none';
          container.classList.add('no-scroll');
          return;
        }

        if (data.names.length === 0) {
          ensureStructure();
          var wrapper = document.getElementById('names-wrapper');
          wrapper.innerHTML = '<div class="empty-state">Be the first to check in today</div>';
          wrapper.style.animation = 'none';
          container.classList.add('no-scroll');
          return;
        }

        ensureStructure();
        var wrapper = document.getElementById('names-wrapper');
        var listHTML = data.names.map(function(name) { return '<li>' + name + '</li>'; }).join('');

        if (data.names.length >= 6) {
          wrapper.innerHTML = '<ul class="names-list">' + listHTML + '</ul><ul class="names-list">' + listHTML + '</ul>';
          var tempList = document.createElement('ul');
          tempList.innerHTML = listHTML;
          tempList.className = 'names-list';
          tempList.style.position = 'absolute';
          tempList.style.visibility = 'hidden';
          document.body.appendChild(tempList);
          var contentHeight = tempList.scrollHeight;
          document.body.removeChild(tempList);
          var duration = contentHeight / SCROLL_SPEED;
          wrapper.style.animation = 'scroll ' + duration + 's linear infinite';
          container.classList.remove('no-scroll');
        } else {
          wrapper.innerHTML = '<ul class="names-list">' + listHTML + '</ul>';
          wrapper.style.animation = 'none';
          container.classList.add('no-scroll');
        }
      } catch (error) {
        console.error('Fetch error:', error);
        ensureStructure();
        var wrapper = document.getElementById('names-wrapper');
        wrapper.innerHTML = '<div class="error-state">Unable to connect to server</div>';
        wrapper.style.animation = 'none';
      }
    }

    // Fullscreen toggle
    var fsBtn = document.getElementById('fullscreen-btn');
    fsBtn.addEventListener('click', function() {
      if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
      } else {
        document.exitFullscreen();
      }
    });
    document.addEventListener('fullscreenchange', function() {
      fsBtn.innerHTML = document.fullscreenElement ? '&#x2716;' : '&#x26F6;';
    });

    fetchCheckIns();
    setInterval(fetchCheckIns, REFRESH_INTERVAL);
  </script>
</body>
</html>
