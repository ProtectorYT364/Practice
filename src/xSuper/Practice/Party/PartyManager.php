<?php

declare(strict_types=1);

namespace xSuper\Practice\Party;

use pocketmine\Player;
use pocketmine\utils\UUID;
use xSuper\Practice\Practice;

class PartyManager
{
    /** @var Practice */
    private $plugin;

    /** @var Party[] */
    private $partys = [];

    public function __construct(Practice $plugin)
    {
        $this->plugin = $plugin;
    }

    public function getParty(string $id): ?Party
    {
        return $this->partys[$id] ?? null;
    }

    /**
     * @return Party[]
     */
    public function getPartys(): array
    {
        return $this->partys;
    }

    public function createParty(Player $player): Party
    {
        $id = UUID::fromRandom()->toString();
        while (isset($this->partys[$id])) $id = UUID::fromRandom()->toString();
        $this->partys[$id] = new Party($id, $player->getName(), [$player->getName()], $this->plugin);
        return $this->partys[$id];
    }

    public function deleteParty(string $id): void
    {
        unset($this->partys[$id]);
    }
}

