<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ECHOBASE – Project Board</title>
  <link rel="stylesheet" href="assets/css/main.css">
  <style>
  .hero {
	  	background-image: url('./<?=APP_LOGO?>');
  }
  </style>
</head>
<body>
  <header>
    <div class="hero">
	  <h1>ECHOBASE</h1>
	</div>
	<div class="controls">
		<button id="theme-toggle" class="theme-btn" aria-label="Toggle theme">
			<span class="theme-icon"></span>
		</button>
		<button id="new-project" class="new-btn">+ New Project</button>
	</div>
  </header>

  <main class="board">
    <!-- Columns will be inserted here by JS -->
  </main>

  <!-- Modal (hidden by default) -->
  <div id="modal" class="modal hidden">
    <div class="modal-content">
      <button class="close-modal">×</button>
      <!-- Tabs + forms inserted by JS -->
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <div id="toast-container"></div>
</body>
</html>