<?php

declare(strict_types=1);

namespace xSuper\Practice\BotDuel;

use xSuper\Practice\Practice;
use pocketmine\scheduler\ClosureTask;

class BotDuelAPI
{
    /** @var Practice */
    private $plugin;

    public function __construct(Practice $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onBotKill(BotDuel $duel): void
    {
        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($duel): void {
            $this->plugin->getBotDuelManager()->deleteDuel($duel->getId());
            $duel->endTask();
            $this->plugin->getMapGenerator()->deleteMap("game_" . $duel->getId());

            $player = $this->plugin->getServer()->getPlayer($duel->getPlayer());
            $this->plugin->getDuelAPI()->resetPlayer($player);
            $this->plugin->getPlayerManager()->getPlayer($player)->setBotDuel(null);
        }), 1);
    }

    public function onPlayerKill(BotDuel $duel): void
    {
        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($duel): void {
            $this->plugin->getBotDuelManager()->deleteDuel($duel->getId());
            $duel->endTask();
            $this->plugin->getMapGenerator()->deleteMap("game_" . $duel->getId());

            $player = $this->plugin->getServer()->getPlayer($duel->getPlayer());
            if ($player !== null) {
                $this->plugin->getDuelAPI()->resetPlayer($player);
                $this->plugin->getPlayerManager()->getPlayer($player)->setBotDuel(null);
            }
        }), 1);
    }


}

