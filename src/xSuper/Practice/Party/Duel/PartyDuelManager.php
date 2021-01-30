<?php

declare(strict_types=1);

namespace xSuper\Practice\Party\Duel;

use pocketmine\utils\UUID;
use xSuper\Practice\Party\Party;
use xSuper\Practice\Practice;

class PartyDuelManager
{
    /** @var Practice */
    private $plugin;

    /** @var PartyDuel[] */
    private $duels = [];

    public function __construct(Practice $plugin)
    {
        $this->plugin = $plugin;
    }

    public function getDuel(string $id): ?PartyDuel
    {
        return $this->duels[$id] ?? null;
    }

    /**
     * @return PartyDuel[]
     */
    public function getDuels(): array
    {
        return $this->duels;
    }

    public function createDuel(Party $party, $type, $map): PartyDuel
    {
        $id = UUID::fromRandom()->toString();
        while (isset($this->duels[$id])) $id = UUID::fromRandom()->toString();
        $this->duels[$id] = new PartyDuel($id, $party, $type, $map, $this->plugin);
        return $this->duels[$id];
    }

    public function deleteDuel(string $id): void
    {
        unset($this->duels[$id]);
    }
}


