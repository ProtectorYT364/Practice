<?php

declare(strict_types=1);

namespace xSuper\Practice\FFA;

use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use xSuper\Practice\Practice;

class FFA
{
    /** @var Practice */
    private $plugin;

    public function __construct(Practice $plugin)
    {
        $this->plugin = $plugin;
    }

    public function loadWorlds (): void
    {
        $this->plugin->getServer()->loadLevel("ffa_nodebuff");
    }

    public function getRandomSpawn(string $arena, Player $player): void
    {
        switch ($arena) {
            case "nodebuff":
                $player->teleport(new Position(262.5, 235, 285.5, $this->plugin->getServer()->getLevelByName("ffa_nodebuff")));
                break;
        }

        $this->initPlayer($arena, $player);
    }

    public function initPlayer(string $arena, Player $player): void
    {
        switch ($arena) {
            case "nodebuff":
                $this->plugin->getKitsAPI()->getKit("nodebuff", $player);
        }

        $this->plugin->getPlayerManager()->getPlayer($player)->setFFA(true);

        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player): void {
            $this->plugin->getPlayerManager()->getPlayer($player)->setCanDamage(true);
        }), 100);
    }

    public function onKill(Player $player): void
    {
        $this->plugin->getDuelAPI()->resetPlayer($player);
        $player->teleport($this->plugin->getServer()->getDefaultLevel()->getSpawnLocation());
        $this->plugin->getPlayerManager()->getPlayer($player)->setFFA(false);
    }

}
