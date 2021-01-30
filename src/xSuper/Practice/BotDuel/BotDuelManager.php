<?php

declare(strict_types=1);

namespace xSuper\Practice\BotDuel;

use pocketmine\entity\Human;
use pocketmine\Player;
use pocketmine\utils\UUID;
use xSuper\Practice\Practice;

class BotDuelManager
{
    /** @var Practice */
    private $plugin;

    /** @var BotDuel[] */
    private $botDuels = [];

    public function __construct(Practice $plugin)
    {
        $this->plugin = $plugin;
    }

    public function getBotDuel(string $id): ?BotDuel
    {
        return $this->botDuels[$id] ?? null;
    }

    /**
     * @return BotDuel[]
     */
    public function getBotDuels(): array
    {
        return $this->botDuels;
    }

    public function createDuel(Player $player, string $type): BotDuel
    {
        $id = UUID::fromRandom()->toString();
        while (isset($this->worlds[$id])) $id = UUID::fromRandom()->toString();
        $this->botDuels[$id] = new BotDuel($id, $player->getName(), $type, $this->plugin);
        return $this->botDuels[$id];
    }

    public function deleteDuel(string $id): void
    {
        unset($this->botDuels[$id]);
    }
}

