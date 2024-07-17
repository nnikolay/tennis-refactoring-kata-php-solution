# Tennis Refactoring Kata - PHP Version - Solution

## The Kata

The Tennis Kata is a well-known refactoring exercise available in many programming languages. You can find the Kata by clicking on this link [Tennis-Refactoring-Kata](https://github.com/emilybache/Tennis-Refactoring-Kata).

## First Step

Before we start the refactoring process, let's see what we have as a starting point:

Run Composer install to install all the dependencies

```
composer install
```

Composer installed all the dependencies successfully, without any problems. The required version of the project is PHP 8, which means we can use many new features introduced in this version.

If you open the [composer.json](composer.json) file, you can see the available commands which we can execute:

```bash
"scripts": {
  "tests": "phpunit",
  "test-coverage": "phpunit --coverage-html build/coverage",
  "check-cs": "ecs check --ansi",
  "fix-cs": "ecs check --fix --ansi",
  "phpstan": "phpstan analyse --ansi"
}
```

### Run the Tests

Let's run the tests to see if everything looks fine.

```
composer tests
```

Everything looks fine. 33 tests with 33 assertions should be sufficient for this small piece of code. We will check later if all cases are covered.

### Run the Test with Coverage

```
composer test-coverage
```

I received the warning:

```
Warning: No code coverage driver available
```

This means that I don't have any driver to generate the test coverage. This is because I usually work with Docker and don't run projects on the local environment. To generate the test coverage, I installed `Xdebug` on my local machine and ran the command again. Now, I received the report.

It looks fine, we have **100% test coverage**. We should run this after the refactoring to ensure we remain with the 100%.

### Run thePHP-CS

```
composer check-cs
```

`Great job - your code is shiny in style!` is the message, but I am not really sure. When I see the different ways of variable naming standards, I think the PHP-CS is not configured as I expect, but this is off-topic.

We will use the camelCase naming convention for variables in our refactoring to keep the code consistent.

### Run PHPStan

```
composer phpstan
```

Oh! Interesting! We see an issue in our code directly. Because PHPStan does a very good job of analyzing PHP code, we should trust it and fix this issue in our refactoring.

## Code Investigation

Okay, we've covered the basics of the project and have an overview of where we stand.

Now let's investigate the code and decide what opportunities we have to create a better version of it.

On the first view:

- The code looks very overloaded with conditionals, making the cognitive complexity very high.
- Additionally, I see inconsistent variable naming and very strange and hard-to-understand names.
- There are also unused variables, which are identified by PHPStan and my IDE as well.
- Hardcoded strings like **'player1'** and **'player2'**.
- The `getScore()` method could use the "early exit" pattern to become more understandable.

Okay, let's start!

## The Refactoring Process

In the refactoring process, we will try to fix the code style, maintainability, and readability of the code.

### Code Style

Let's first fix the code style and use a consistent way of naming variables. I will rename the variables:

```php
private int $m_score1 = 0;
private int $m_score2 = 0;
```

to

```php
private int $player1Score = 0;
private int $player2Score = 0;
```

This way, we have everything in `camelCase` and also have clearer names for the score variables.

### The wonPoint() Method

Now let's have a look at the `wonPoint()` method:

```php
public function __construct(
    private string $player1Name,
    private string $player2Name
) {
}

public function wonPoint(string $playerName): void
{
    if ($playerName === 'player1') {
        $this->player1Score++;
    } else {
        $this->player2Score++;
    }
}
```

The `$player1Name` and `$player2Name` variables are not used anywhere, but they are part of the code, and all the tests are prepared in that way. We cannot remove the variables; otherwise, we will break the rule of refactoring to not change the behavior of the code.

On the first view, we see that we could replace 'player1' with `$this->$player1Name` and fix two issues:
- We no longer have a comparison to a hardcoded string and could use any name for the first player, and the code will work.
- The `$player1Name` variable is used, and we solved one problem from PHPStan.

The method now looks like this:

```php
public function wonPoint(string $playerName): void
{
    if ($playerName === $this->player1Name) {
        $this->player1Score++;
    } else {
        $this->player2Score++;
    }
}
```

However, there is a bigger issue in my opinion. The code doesn't care who the second player is and gives the point to them when `$playerName` does not equal `$this->player1Name`. This seems not right to me. Why would I receive the name of the second player in the constructor when creating the game if we don't care about that?

That's why I will refactor the method to ensure that the value of `$playerName` makes sense and the expectation of my code is clear. Otherwise, it should throw an error if a player name is given that doesn't play in this match. Makes sense, doesn't it?

```php
public function wonPoint(string $playerName): void
{
    match ($playerName) {
        $this->player1Name => $this->player1Score++,
        $this->player2Name => $this->player2Score++,
        default => throw new \InvalidArgumentException(
            sprintf(
              'Player with the name "%s" does not play in this match.', 
              $playerName
            )
        ),
    };
}
```

Now, the code makes sense to me, and I am 100% confident that it does what I expect. The tests pass as well, but in real life, we should extend the tests with this new negative case.

When we run PHPStan again, we see that we fixed the errors for `TennisGame1`, which is great.

### The getScore() Method

This method is the biggest problem in the code. The cognitive complexity is very high because of the nested if-else conditions.

Let's start from the beginning and see what we can do.

**First Block**

Let's have a look at the first block:

```php
if ($this->player1Score === $this->player2Score) {
    $score = match ($this->player1Score) {
        0 => 'Love-All',
        1 => 'Fifteen-All',
        2 => 'Thirty-All',
        default => 'Deuce',
    };
} else { ... }
```

What we notice immediately is that if `$this->player1Score === $this->player2Score`, we can return the result directly without executing the rest of the code. This means we don't need the else anymore. We replace it with an if and make the code more readable.

So, we can modify the code like this:

```php
public function getScore(): string
{
    $score = '';

    if ($this->player1Score === $this->player2Score) {
        return match ($this->player1Score) {
            0 => 'Love-All',
            1 => 'Fifteen-All',
            2 => 'Thirty-All',
            default => 'Deuce',
        };
    }

    if ($this->player1Score >= 4 || $this->player2Score >= 4) {
        $minusResult = $this->player1Score - $this->player2Score;
        if ($minusResult === 1) {
            $score = 'Advantage player1';
        } elseif ($minusResult === -1) {
            $score = 'Advantage player2';
        } elseif ($minusResult >= 2) {
            $score = 'Win for player1';
        } else {
            $score = 'Win for player2';
        }
    } else {
        for ($i = 1; $i < 3; $i++) {
            if ($i === 1) {
                $tempScore = $this->player1Score;
            } else {
                $score .= '-';
                $tempScore = $this->player2Score;
            }
            switch ($tempScore) {
                case 0:
                    $score .= 'Love';
                    break;
                case 1:
                    $score .= 'Fifteen';
                    break;
                case 2:
                    $score .= 'Thirty';
                    break;
                case 3:
                    $score .= 'Forty';
                    break;
            }
        }
    }

    return $score;
}
```

**Second Block**

Now let's have a look at the second block:

This block means that at least one of the players has reached 4 points or more, which means the game is in an advanced or winning state.

```php
if ($this->player1Score >= 4 || $this->player2Score >= 4) {
    $minusResult = $this->player1Score - $this->player2Score;
    if ($minusResult === 1) {
        $score = 'Advantage player1';
    } elseif ($minusResult === -1) {
        $score = 'Advantage player2';
    } elseif ($minusResult >= 2) {
        $score = 'Win for player1';
    } else {
        $score = 'Win for player2';
    }
} else { ... }
```

By checking the second condition, `$this->player1Score >= 4 || $this->player2Score >= 4`, we see that we can determine and return the result immediately without processing the next lines of code. To achieve this, we need to slightly modify the inner parts and transform them into a `match()` statement.

After refactoring, the code looks like this:

```php
public function getScore(): string
{
    $score = '';

    if ($this->player1Score === $this->player2Score) {
        return match ($this->player1Score) {
            0 => 'Love-All',
            1 => 'Fifteen-All',
            2 => 'Thirty-All',
            default => 'Deuce',
        };
    }

    if ($this->player1Score >= 4 || $this->player2Score >= 4) {
        $minusResult = $this->player1Score - $this->player2Score;

        return match (true) {
            $minusResult === 1 => 'Advantage player1',
            $minusResult === -1 => 'Advantage player2',
            $minusResult >= 2 => 'Win for player1',
            default => 'Win for player2',
        };
    }

    for ($i = 1; $i < 3; $i++) {
        if ($i === 1) {
            $tempScore = $this->player1Score;
        } else {
            $score .= '-';
            $tempScore = $this->player2Score;
        }
        switch ($tempScore) {
            case 0:
                $score .= 'Love';
                break;
            case 1:
                $score .= 'Fifteen';
                break;
            case 2:
                $score .= 'Thirty';
                break;
            case 3:
                $score .= 'Forty';
                break;
        }
    }
    
    return $score;
}
```

The second block is now more readable, and we no longer need the else statement because we only proceed if both initial conditions are not met.

Additionally, it doesn't make sense to have "player1" and "player2" hardcoded in the output. Imagine you initialize the game with:

```php
$game = new TennisGame1('Roger Federer', 'Novak Djokovic');
```

If the output then says **"Advantage player1"**, it doesn't make sense. We should call the players by their given names.

That's why I added the variables from the beginning of the game to have a clearer output.

```php
$minusResult = $this->player1Score - $this->player2Score;

return match (true) {
    $minusResult === 1 => sprintf('Advantage %s', $this->player1Name),
    $minusResult === -1 => sprintf('Advantage %s', $this->player2Name),
    $minusResult >= 2 => sprintf('Win for %s', $this->player1Name),
    default => sprintf('Win for %s', $this->player2Name),
};
```

The next issue here is the variable name `$minusResult`. Naming variables can be tricky. To me, this name sounds strange, which is why I'm renaming it to something clearer that indicates exactly what value it stores.

In my opinion, a better name would be `$scoreDifference` because it precisely represents the operation `$this->player1Score - $this->player2Score`.

The block now looks like this:

```php
$scoreDifference = $this->player1Score - $this->player2Score;

return match (true) {
    $scoreDifference === 1 => sprintf('Advantage %s', $this->player1Name),
    $scoreDifference === -1 => sprintf('Advantage %s', $this->player2Name),
    $scoreDifference >= 2 => sprintf('Win for %s', $this->player1Name),
    default => sprintf('Win for %s', $this->player2Name),
};
```

**Third Block**

Now let's have a look at the third block:

```php
for ($i = 1; $i < 3; $i++) {
    if ($i === 1) {
        $tempScore = $this->player1Score;
    } else {
        $score .= '-';
        $tempScore = $this->player2Score;
    }
    switch ($tempScore) {
        case 0:
            $score .= 'Love';
            break;
        case 1:
            $score .= 'Fifteen';
            break;
        case 2:
            $score .= 'Thirty';
            break;
        case 3:
            $score .= 'Forty';
            break;
    }
}
```

Looks quite messy to me. Let's break down what it actually does. As a tennis player, it's clear to me that this represents the current score, similar to what the referee announces after each point. For instance, **"Love-Fifteen"** corresponds to a score of **"0–15"** from the perspective of who is serving.

So, the code here involves a loop that iterates only twice: first to fetch the result for the first player and then for the second player, combining them into a string separated by a hyphen, like **"Love-Fifteen"**.

It seems overly complex for such a simple task.

To streamline this, we notice that the switch statement is identical for both players. It would be best to extract this into a private function.

I've created a new private method `getScoreName()`, and now the code looks like this:

```php
class TennisGame1 implements TennisGame
{
    // ...
    
    public function getScore(): string
    {
        $score = '';
    
        // ...
    
        for ($i = 1; $i < 3; $i++) {
            if ($i === 1) {
                $tempScore = $this->player1Score;
            } else {
                $score .= '-';
                $tempScore = $this->player2Score;
            }
            
            $score .= $this->getScoreName($tempScore);
        }
    
        return $score;
    }
    
    private function getScoreName(int $score): string
    {
        $scoreText = '';
        switch ($score) {
            case 0:
                $scoreText = 'Love';
                break;
            case 1:
                $scoreText = 'Fifteen';
                break;
            case 2:
                $scoreText = 'Thirty';
                break;
            case 3:
                $scoreText = 'Forty';
                break;
        }
    
        return $scoreText;
    }
}
```

Well, it looks a bit more organized now, but it's still far from being clean and understandable, especially with the cumbersome switch statement, the for loop, the if-else blocks, and the `$tempScore` variable.

Let's start by transforming the `switch` statement into a more concise `match` statement. We'll include a default value and ensure clear error handling for scores that don't fit within the tennis game rules.

```php
class TennisGame1 implements TennisGame
{
    // ...

    public function getScore(): string
    {
        $score = '';

        // ...

        for ($i = 1; $i < 3; $i++) {
            if ($i === 1) {
                $tempScore = $this->player1Score;
            } else {
                $score .= '-';
                $tempScore = $this->player2Score;
            }
            $score .= $this->getScoreName($tempScore);
        }

        return $score;
    }

    private function getScoreName(int $score): string
    {
        return match ($score) {
            0 => 'Love',
            1 => 'Fifteen',
            2 => 'Thirty',
            3 => 'Forty',
            default => throw new \InvalidArgumentException(
              sprintf('Invalid score: %s', $score)
            ),
        };
    }
}
```

Now that we have introduced the private method, we understand that the for loop iterates only twice because there are two players. Therefore, we can replace this for loop with two calls to the private method instead. 

Additionally, we can remove the initialization of the `$score` variable at the beginning of the function because it is no longer necessary.

```php
class TennisGame1 implements TennisGame
{
    // ...

    public function getScore(): string
    {
        // ...

        return $this->getScoreName($this->player1Score) . '-' . $this->getScoreName($this->player2Score);
    }

    private function getScoreName(int $score): string
    {
        return match ($score) {
            0 => 'Love',
            1 => 'Fifteen',
            2 => 'Thirty',
            3 => 'Forty',
            default => throw new \InvalidArgumentException(
              sprintf('Invalid score: %s', $score)
            ),
        };
    }
}
```

And to finalize the refactoring, I will use `sprintf()` for string formatting because it makes the code clearer to read, is type-safe, and reduces the chance of errors.

```php
class TennisGame1 implements TennisGame
{
    // ...

    public function getScore(): string
    {
        // ...

        return sprintf(
            '%s-%s',
            $this->getScoreName($this->player1Score),
            $this->getScoreName($this->player2Score)
        );
    }

    private function getScoreName(int $score): string
    {
        return match ($score) {
            0 => 'Love',
            1 => 'Fifteen',
            2 => 'Thirty',
            3 => 'Forty',
            default => throw new \InvalidArgumentException(
              sprintf('Invalid score: %s', $score)
            ),
        };
    }
}
```

**Single Responsibility Principle (SRP)**

Looking at the `getScore()` method, it's clear that it handles multiple cases. To adhere to the Single Responsibility Principle and ensure clear responsibility for each method, we'll extract the different parts into private methods.

Let's create two private methods: `getEqualScore()` and `getAdvantageOrWin()`. These methods will handle the respective scenarios for equal scores and advantage or win situations.

```php
class TennisGame1 implements TennisGame
{
    // ...

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
            0 => 'Love-All',
            1 => 'Fifteen-All',
            2 => 'Thirty-All',
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
            0 => 'Love',
            1 => 'Fifteen',
            2 => 'Thirty',
            3 => 'Forty',
            default => throw new \InvalidArgumentException(
              sprintf('Invalid score: %s', $score)
            ),
        };
    }
}
```

Upon reviewing the refactored code again, it's evident that we have two match expressions where the same matching keys `(0, 1, 2, and 3)` are used with similar meanings. To enhance clarity and avoid magic numbers in our code, we should define constants for these score values.

Let's define constants such as `LOVE`, `FIFTEEN`, `THIRTY`, and `FORTY` to represent these score values throughout the codebase. This approach not only improves readability but also makes the code more maintainable by centralizing these values.

```php
class TennisGame1 implements TennisGame
{
    private const LOVE = 0;
    private const FIFTEEN = 1;
    private const THIRTY = 2;
    private const FORTY = 3;

    // ...

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
```

## Final Code

That's it. We successfully refactored the code quickly, making it much more readable, understandable, and cleaner.

Here's the file with the complete code: [TennisGame1.php](src/TennisGame1.php)

Happy coding!
