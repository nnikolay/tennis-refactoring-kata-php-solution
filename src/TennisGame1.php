<?php

declare(strict_types=1);

namespace TennisGame;

class TennisGame1 implements TennisGame
{
    private const LOVE = 0;

    private const FIFTEEN = 1;

    private const THIRTY = 2;

    private const FORTY = 3;

    private int $player1Score = 0;

    private int $player2Score = 0;

    public function __construct(
        private string $player1Name,
        private string $player2Name,
    ) {
    }

    public function wonPoint(string $playerName): void
    {
        match ($playerName) {
            $this->player1Name => $this->player1Score++,
            $this->player2Name => $this->player2Score++,
            default => throw new \InvalidArgumentException(
                sprintf('Player with the name "%s" does not play in this match.', $playerName)
            ),
        };
    }

    public function getScore(): string
    {
        if ($this->player1Score === $this->player2Score) {
            return $this->getEqualScore();
        }

        if ($this->player1Score >= 4 || $this->player2Score >= 4) {
            return $this->getAdvantageOrWin();
        }

        return sprintf(
            '%s-%s',
            $this->getScoreName($this->player1Score),
            $this->getScoreName($this->player2Score)
        );
    }

    private function getEqualScore(): string
    {
        return match ($this->player1Score) {
            self::LOVE => 'Love-All',
            self::FIFTEEN => 'Fifteen-All',
            self::THIRTY => 'Thirty-All',
            default => 'Deuce',
        };
    }

    private function getAdvantageOrWin(): string
    {
        $scoreDifference = $this->player1Score - $this->player2Score;

        return match (true) {
            $scoreDifference === 1 => sprintf('Advantage %s', $this->player1Name),
            $scoreDifference === -1 => sprintf('Advantage %s', $this->player2Name),
            $scoreDifference >= 2 => sprintf('Win for %s', $this->player1Name),
            default => sprintf('Win for %s', $this->player2Name),
        };
    }

    private function getScoreName(int $score): string
    {
        return match ($score) {
            self::LOVE => 'Love',
            self::FIFTEEN => 'Fifteen',
            self::THIRTY => 'Thirty',
            self::FORTY => 'Forty',
            default => throw new \InvalidArgumentException(
                sprintf('Invalid score: %s', $score)
            ),
        };
    }
}
