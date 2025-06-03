class MemoryGame {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.symbols = ['🐶', '🐱', '🐭', '🐰', '🦊', '🐼'];
        this.cards = [...this.symbols, ...this.symbols];
        this.flippedCards = [];
        this.matchedPairs = 0;
        this.errorCount = 0;
        this.gamesCompleted = 0;
        this.canFlip = true;
    }

    init() {
        this.shuffleCards();
        this.renderGame();
        this.updateStats();
    }

    shuffleCards() {
        for (let i = this.cards.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [this.cards[i], this.cards[j]] = [this.cards[j], this.cards[i]];
        }
    }

    renderGame() {
        this.container.innerHTML = '';
        this.cards.forEach((symbol, index) => {
            const card = document.createElement('div');
            card.className = 'memory-card';
            card.dataset.index = index;
            card.textContent = '?';
            card.dataset.symbol = symbol;
            card.addEventListener('click', () => this.handleCardClick(card));
            this.container.appendChild(card);
        });
    }

    handleCardClick(card) {
        if (!this.canFlip || card.classList.contains('flipped') || card.classList.contains('matched')) {
            return;
        }

        card.classList.add('flipped');
        card.textContent = card.dataset.symbol;
        this.flippedCards.push(card);

        if (this.flippedCards.length === 2) {
            this.checkForMatch();
        }
    }

    checkForMatch() {
        this.canFlip = false;
        const [card1, card2] = this.flippedCards;

        if (card1.dataset.symbol === card2.dataset.symbol) {
            card1.classList.add('matched');
            card2.classList.add('matched');
            this.flippedCards = [];
            this.matchedPairs++;
            this.canFlip = true;

            if (this.matchedPairs === this.symbols.length) {
                this.handleGameCompletion();
            }
        } else {
            this.errorCount++;
            this.updateStats();
            setTimeout(() => {
                card1.classList.remove('flipped');
                card2.classList.remove('flipped');
                card1.textContent = '?';
                card2.textContent = '?';
                this.flippedCards = [];
                this.canFlip = true;
            }, 500);
        }
    }

    handleGameCompletion() {
        this.gamesCompleted++;
        this.updateStats();
        setTimeout(() => {
            this.resetGame();
        }, 500);
    }

    resetGame() {
        this.matchedPairs = 0;
        this.shuffleCards();
        this.renderGame();
    }

    updateStats() {
        document.getElementById('games-completed').textContent = this.gamesCompleted;
        document.getElementById('error-count').textContent = this.errorCount;
    }
}

// Initialize game when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const game = new MemoryGame('memory-game-container');
    game.init();
});