<?php

declare(strict_types=1);

namespace xSuper\Practice\Player;

use pocketmine\Player;
use pocketmine\utils\UUID;
use xSuper\Practice\Practice;

class PlayerManager
{
    /** @var Practice */
    private $plugin;

    /** @var PracticePlayer[] */
    private $players = [];

    public function __construct(Practice $plugin)
    {
        $this->plugin = $plugin;

        $plugin->getDatabase()->executeSelect("practice.players.load", [], function (array $rows): void {
            foreach ($rows as $row) {
                $this->players[$row["uuid"]] = new PracticePlayer(UUID::fromString($row["uuid"]), $row["username"], $row["elo"], $row["crowns"], $row["rank"], $this->plugin);
            }
        });
    }

    public function createPlayer(Player $player): PracticePlayer
    {
        $this->plugin->getDatabase()->executeInsert("practice.players.create", [
            "uuid" => $player->getUniqueId()->toString(),
            "username" => $player->getName(),
            "elo" => 1000,
            "crowns" => 0,
            "rank" => "guest"
        ]);
        $this->players[$player->getUniqueId()->toString()] = new PracticePlayer($player->getUniqueId(), $player->getName(), 1000, 0, "guest", $this->plugin);
        return $this->players[$player->getUniqueId()->toString()];
    }

    public function getPlayer(Player $player): ?PracticePlayer
    {
        return $this->getPlayerByUUID($player->getUniqueId());
    }

    public function getPlayerByUUID(UUID $uuid): ?PracticePlayer
    {
        return $this->players[$uuid->toString()] ?? null;
    }

    public function getPlayerByName(string $name): ?PracticePlayer
    {
        foreach ($this->players as $player) {
            if (strtolower($player->getUsername()) === strtolower($name)) return $player;
        }
        return null;
    }

    /**
     * @return PracticePlayer[]
     */
    public function getPlayers(): array
    {
        return $this->players;
    }
}

