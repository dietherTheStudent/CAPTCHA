<?php session_start(); ?>

<form method="post" action="verify.php">
  Username: <input type="text" name="username"><br><br>
  Password: <input type="password" name="password"><br><br>

  <!-- TEXT CAPTCHA (default) -->
  <div id="text-section">
    Enter CAPTCHA: <input type="text" name="captcha" id="captcha-input" autocomplete="off"><br><br>
    <img id="captcha-img" src="captcha.php" alt="CAPTCHA"><br><br>
    <button type="button" onclick="refreshCaptcha()">Reload CAPTCHA</button>
    <span id="timer">10s</span><br><br>
  </div>

  <!-- IMAGE CAPTCHA (optional) -->
  <div id="image-section" style="display:none;">
    <p style="font-size:13px; margin:0 0 6px;">Click all tiles that contain a <b>car</b>.</p>

    <div id="puzzle-grid" style="
      display: grid;
      grid-template-columns: repeat(4, 75px);
      grid-template-rows: repeat(4, 75px);
      gap: 2px;
      margin-bottom: 8px;
    "></div>

    <p id="puzzle-status" style="font-size:13px; margin:4px 0;"></p>

    <button type="button" onclick="verifyPuzzle()">Verify</button>
    <button type="button" onclick="shufflePuzzleTiles()">Shuffle</button>
    <button type="button" onclick="newPuzzle()">&#x21bb; Reload</button>
    <span id="timer-image" style="font-size:13px; color:red; margin-left:6px;"></span><br><br>

    <input type="hidden" name="image_captcha_passed" id="image_captcha_passed" value="0">
  </div>

  <!-- MODE TOGGLE BUTTONS -->
  <button type="button" onclick="setMode('text')">TEXT</button>
  <button type="button" onclick="setMode('image')">IMAGE</button><br><br>

  <input type="submit" value="Login">
</form>

<script>
  // -------------------------------------------------------
  // PUZZLE DATA — add as many puzzles as you want.
  // image: path to your street scene image
  // cols/rows: how many tiles to slice it into
  // answer: flat tile indexes (0 = top-left, goes left→right, top→bottom)
  //         that contain a car
  // -------------------------------------------------------
  const PUZZLES = [
    {
      image: './images/street1.jpg',
      cols: 4,
      rows: 4,
      answer: [5, 6, 7, 9, 10, 11, 13, 14, 15] // <-- set these to match your image
    },
    {
      image: './images/street2.jpg',
      cols: 4,
      rows: 4,
      answer: [5, 6, 8, 9, 10, 11, 12, 13, 14, 15]
    }
  ];

  let currentPuzzle = null;
  let selectedTiles = [];
  let timerInterval, countdown;

  // ---- MODE SWITCHING ----
  function setMode(mode) {
    clearInterval(timerInterval);
    document.getElementById('text-section').style.display  = mode === 'text'  ? 'block' : 'none';
    document.getElementById('image-section').style.display = mode === 'image' ? 'block' : 'none';
    if (mode === 'text')  startTextTimer();
    if (mode === 'image') newPuzzle();
  }

  // ---- TEXT CAPTCHA ----
  function refreshCaptcha() {
    document.getElementById('captcha-img').src = 'captcha.php?t=' + Date.now();
    document.getElementById('captcha-input').value = '';
    startTextTimer();
  }

  function startTextTimer() {
    clearInterval(timerInterval);
    countdown = 10;
    const el = document.getElementById('timer');
    el.textContent = countdown + 's';
    timerInterval = setInterval(() => {
      countdown--;
      el.textContent = countdown + 's';
      if (countdown <= 0) refreshCaptcha();
    }, 1000);
  }

  // ---- IMAGE CAPTCHA ----
  function newPuzzle() {
    clearInterval(timerInterval);
    selectedTiles = [];
    document.getElementById('puzzle-status').textContent = '';
    document.getElementById('image_captcha_passed').value = '0';

    // pick a random puzzle
    currentPuzzle = PUZZLES[Math.floor(Math.random() * PUZZLES.length)];

    const { image, cols, rows } = currentPuzzle;
    const tileW = 75, tileH = 75;
    const totalW = cols * tileW;
    const totalH = rows * tileH;

    const grid = document.getElementById('puzzle-grid');
    grid.style.gridTemplateColumns = `repeat(${cols}, ${tileW}px)`;
    grid.style.gridTemplateRows    = `repeat(${rows}, ${tileH}px)`;
    grid.innerHTML = '';

    for (let i = 0; i < cols * rows; i++) {
      const col = i % cols;
      const row = Math.floor(i / cols);

      const div = document.createElement('div');
      div.style.cssText = `
        width: ${tileW}px;
        height: ${tileH}px;
        background-image: url('${image}');
        background-size: ${totalW}px ${totalH}px;
        background-position: -${col * tileW}px -${row * tileH}px;
        box-sizing: border-box;
        border: 2px solid transparent;
        cursor: pointer;
      `;
      div.dataset.index = i;

      div.onclick = () => {
        const idx = parseInt(div.dataset.index);
        if (div.classList.contains('selected')) {
          div.classList.remove('selected');
          div.style.border = '2px solid transparent';
          selectedTiles = selectedTiles.filter(s => s !== idx);
        } else {
          div.classList.add('selected');
          div.style.border = '2px solid blue';
          selectedTiles.push(idx);
        }
      };

      grid.appendChild(div);
    }

    startImageTimer();
  }

  function verifyPuzzle() {
    const { answer } = currentPuzzle;
    const tiles = document.querySelectorAll('#puzzle-grid div');

    let pass = true;

    tiles.forEach(tile => {
      const i = parseInt(tile.dataset.index);
      const isCar  = answer.includes(i);
      const isSel  = selectedTiles.includes(i);

      tile.classList.remove('selected');

      if (isCar && isSel) {
        tile.style.border = '2px solid green';
      } else if (isCar && !isSel) {
        tile.style.border = '2px solid red'; // missed a car
        pass = false;
      } else if (!isCar && isSel) {
        tile.style.border = '2px solid red'; // selected a non-car
        pass = false;
      } else {
        tile.style.border = '2px solid transparent';
      }
    });

    const status = document.getElementById('puzzle-status');
    if (pass && selectedTiles.length === answer.length) {
      status.textContent = '✓ Correct!';
      status.style.color = 'green';
      document.getElementById('image_captcha_passed').value = '1';
      clearInterval(timerInterval);
    } else {
      status.textContent = '✗ Incorrect. Try again.';
      status.style.color = 'red';
      setTimeout(newPuzzle, 1500);
    }
  }

  function startImageTimer() {
    clearInterval(timerInterval);
    countdown = 15;
    const el = document.getElementById('timer-image');
    el.textContent = countdown + 's';
    timerInterval = setInterval(() => {
      countdown--;
      el.textContent = countdown + 's';
      if (countdown <= 0) newPuzzle();
    }, 1000);
  }
  function shufflePuzzleTiles() {
    const grid = document.getElementById('puzzle-grid');
    const tiles = [...grid.children];
    if (!tiles.length || !currentPuzzle) return;

    const { image, cols, rows } = currentPuzzle;
    const tileW = 75, tileH = 75;
    const totalW = cols * tileW, totalH = rows * tileH;
    const n = cols * rows;

    const order = Array.from({ length: n }, (_, i) => i);
    for (let i = n - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [order[i], order[j]] = [order[j], order[i]];
    }

    tiles.forEach((div, pos) => {
      const src = order[pos];
      const col = src % cols;
      const row = Math.floor(src / cols);
      div.style.backgroundPosition = `-${col * tileW}px -${row * tileH}px`;
      div.dataset.index = src;
      div.classList.remove('selected');
      div.style.border = '2px solid transparent';
    });

    selectedTiles = [];
    document.getElementById('puzzle-status').textContent = '';
  }

  // Start in text mode
  startTextTimer();
</script>