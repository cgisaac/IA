const canvas = document.getElementById('gameCanvas');
const ctx = canvas.getContext('2d');
const scoreElement = document.getElementById('score');
const highScoreElement = document.getElementById('high-score');
const startBtn = document.getElementById('startBtn');
const pauseBtn = document.getElementById('pauseBtn');

// Configurações do jogo
const gridSize = 20;
const tileCount = canvas.width / gridSize;

let snake = [];
let food = {};
let direction = 'right';
let nextDirection = 'right';
let score = 0;
let highScore = localStorage.getItem('snakeHighScore') || 0;
let gameLoop;
let isGameRunning = false;
let isPaused = false;
let gameSpeed = 150;

// Inicializa o recorde na tela
highScoreElement.textContent = highScore;

// Desenha o tabuleiro
function drawBoard() {
    ctx.fillStyle = '#f0f0f0';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    // Desenha as linhas da grade
    ctx.strokeStyle = '#e0e0e0';
    ctx.lineWidth = 0.5;
    
    for (let i = 0; i <= tileCount; i++) {
        ctx.beginPath();
        ctx.moveTo(i * gridSize, 0);
        ctx.lineTo(i * gridSize, canvas.height);
        ctx.stroke();
        
        ctx.beginPath();
        ctx.moveTo(0, i * gridSize);
        ctx.lineTo(canvas.width, i * gridSize);
        ctx.stroke();
    }
}

// Desenha a cobrinha
function drawSnake() {
    snake.forEach((segment, index) => {
        // Cabeça é mais escura, corpo é mais claro
        if (index === 0) {
            ctx.fillStyle = '#4CAF50';
        } else {
            ctx.fillStyle = '#8BC34A';
        }
        
        ctx.fillRect(
            segment.x * gridSize + 1,
            segment.y * gridSize + 1,
            gridSize - 2,
            gridSize - 2
        );
        
        // Desenha os olhos na cabeça
        if (index === 0) {
            ctx.fillStyle = 'white';
            
            let eyeX1, eyeY1, eyeX2, eyeY2;
            const eyeSize = 4;
            const eyeOffset = 5;
            
            switch(direction) {
                case 'up':
                    eyeX1 = segment.x * gridSize + eyeOffset;
                    eyeY1 = segment.y * gridSize + eyeOffset;
                    eyeX2 = segment.x * gridSize + gridSize - eyeOffset - eyeSize;
                    eyeY2 = segment.y * gridSize + eyeOffset;
                    break;
                case 'down':
                    eyeX1 = segment.x * gridSize + eyeOffset;
                    eyeY1 = segment.y * gridSize + gridSize - eyeOffset - eyeSize;
                    eyeX2 = segment.x * gridSize + gridSize - eyeOffset - eyeSize;
                    eyeY2 = segment.y * gridSize + gridSize - eyeOffset - eyeSize;
                    break;
                case 'left':
                    eyeX1 = segment.x * gridSize + eyeOffset;
                    eyeY1 = segment.y * gridSize + eyeOffset;
                    eyeX2 = segment.x * gridSize + eyeOffset;
                    eyeY2 = segment.y * gridSize + gridSize - eyeOffset - eyeSize;
                    break;
                case 'right':
                    eyeX1 = segment.x * gridSize + gridSize - eyeOffset - eyeSize;
                    eyeY1 = segment.y * gridSize + eyeOffset;
                    eyeX2 = segment.x * gridSize + gridSize - eyeOffset - eyeSize;
                    eyeY2 = segment.y * gridSize + gridSize - eyeOffset - eyeSize;
                    break;
            }
            
            ctx.fillRect(eyeX1, eyeY1, eyeSize, eyeSize);
            ctx.fillRect(eyeX2, eyeY2, eyeSize, eyeSize);
        }
    });
}

// Desenha a comida
function drawFood() {
    ctx.fillStyle = '#FF5722';
    ctx.beginPath();
    ctx.arc(
        food.x * gridSize + gridSize / 2,
        food.y * gridSize + gridSize / 2,
        gridSize / 2 - 2,
        0,
        Math.PI * 2
    );
    ctx.fill();
    
    // Brilho na comida
    ctx.fillStyle = '#FF8A65';
    ctx.beginPath();
    ctx.arc(
        food.x * gridSize + gridSize / 2 - 2,
        food.y * gridSize + gridSize / 2 - 2,
        gridSize / 4,
        0,
        Math.PI * 2
    );
    ctx.fill();
}

// Gera comida em posição aleatória
function generateFood() {
    let newFood;
    do {
        newFood = {
            x: Math.floor(Math.random() * tileCount),
            y: Math.floor(Math.random() * tileCount)
        };
    } while (snake.some(segment => segment.x === newFood.x && segment.y === newFood.y));
    
    food = newFood;
}

// Move a cobrinha
function moveSnake() {
    direction = nextDirection;
    
    const head = { ...snake[0] };
    
    switch(direction) {
        case 'up':
            head.y--;
            break;
        case 'down':
            head.y++;
            break;
        case 'left':
            head.x--;
            break;
        case 'right':
            head.x++;
            break;
    }
    
    // Verifica colisão com paredes
    if (head.x < 0 || head.x >= tileCount || head.y < 0 || head.y >= tileCount) {
        gameOver();
        return;
    }
    
    // Verifica colisão com o próprio corpo
    if (snake.some(segment => segment.x === head.x && segment.y === head.y)) {
        gameOver();
        return;
    }
    
    snake.unshift(head);
    
    // Verifica se comeu a comida
    if (head.x === food.x && head.y === food.y) {
        score += 10;
        scoreElement.textContent = score;
        generateFood();
        
        // Aumenta a velocidade a cada 50 pontos
        if (score % 50 === 0 && gameSpeed > 50) {
            clearInterval(gameLoop);
            gameSpeed -= 10;
            gameLoop = setInterval(update, gameSpeed);
        }
    } else {
        snake.pop();
    }
}

// Atualiza o jogo
function update() {
    if (!isPaused) {
        drawBoard();
        drawFood();
        moveSnake();
        drawSnake();
    }
}

// Game Over
function gameOver() {
    clearInterval(gameLoop);
    isGameRunning = false;
    
    // Atualiza recorde
    if (score > highScore) {
        highScore = score;
        localStorage.setItem('snakeHighScore', highScore);
        highScoreElement.textContent = highScore;
    }
    
    // Desenha mensagem de game over
    ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    ctx.fillStyle = 'white';
    ctx.font = 'bold 30px Arial';
    ctx.textAlign = 'center';
    ctx.fillText('Game Over!', canvas.width / 2, canvas.height / 2 - 20);
    
    ctx.font = '20px Arial';
    ctx.fillText(`Pontuação: ${score}`, canvas.width / 2, canvas.height / 2 + 20);
    
    startBtn.disabled = false;
    pauseBtn.disabled = true;
    pauseBtn.textContent = 'Pausar';
}

// Inicia o jogo
function startGame() {
    if (isGameRunning) return;
    
    snake = [
        { x: 10, y: 10 },
        { x: 9, y: 10 },
        { x: 8, y: 10 }
    ];
    
    direction = 'right';
    nextDirection = 'right';
    score = 0;
    gameSpeed = 150;
    scoreElement.textContent = score;
    
    generateFood();
    isGameRunning = true;
    isPaused = false;
    
    startBtn.disabled = true;
    pauseBtn.disabled = false;
    
    gameLoop = setInterval(update, gameSpeed);
}

// Pausa/Continua o jogo
function togglePause() {
    if (!isGameRunning) return;
    
    isPaused = !isPaused;
    pauseBtn.textContent = isPaused ? 'Continuar' : 'Pausar';
}

// Controla as teclas
document.addEventListener('keydown', (e) => {
    switch(e.key) {
        case 'ArrowUp':
            if (direction !== 'down') nextDirection = 'up';
            e.preventDefault();
            break;
        case 'ArrowDown':
            if (direction !== 'up') nextDirection = 'down';
            e.preventDefault();
            break;
        case 'ArrowLeft':
            if (direction !== 'right') nextDirection = 'left';
            e.preventDefault();
            break;
        case 'ArrowRight':
            if (direction !== 'left') nextDirection = 'right';
            e.preventDefault();
            break;
        case ' ':
            if (isGameRunning) togglePause();
            e.preventDefault();
            break;
    }
});

// Event listeners dos botões
startBtn.addEventListener('click', startGame);
pauseBtn.addEventListener('click', togglePause);

// Desenha a tela inicial
drawBoard();
ctx.fillStyle = '#667eea';
ctx.font = 'bold 20px Arial';
ctx.textAlign = 'center';
ctx.fillText('Clique em "Iniciar Jogo" para começar', canvas.width / 2, canvas.height / 2);
