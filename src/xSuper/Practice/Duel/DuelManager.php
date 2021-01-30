<?php

declare(strict_types=1);

namespace xSuper\Practice\Duel;

use pocketmine\Player;
use pocketmine\utils\UUID;
use xSuper\Practice\Practice;

class DuelManager
{
    /** @var Practice */
    private $plugin;

    /** @var Duel[] */
    private $duels = [];

    public function __construct(Practice $plugin)
    {
        $this->plugin = $plugin;
    }

    public function getDuel(string $id): ?Duel
    {
        return $this->duels[$id] ?? null;
    }

    /**
     * @return Duel[]
     */
    public function getDuels(): array
    {
        return $this->duels;
    }

    public function createDuel(Player $player, string $type, bool $ranked, string $map): Duel
    {
        $id = UUID::fromRandom()->toString();
        while (isset($this->duels[$id])) $id = UUID::fromRandom()->toString();
        $this->duels[$id] = new Duel($id, [$player->getName()], $type, $ranked, $this->plugin, $map);
        return $this->duels[$id];
    }

    public function deleteDuel(string $id): void
    {
        unset($this->duels[$id]);
    }
}
