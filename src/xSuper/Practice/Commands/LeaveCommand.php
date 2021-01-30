<?php

namespace xSuper\Practice\Commands;

use pocketmine\{command\CommandSender, command\PluginCommand, Player};
use xSuper\Practice\Practice;

class LeaveCommand extends PluginCommand
{
    /** @var Practice */
    private $plugin;

    public function __construct(Practice $owner)
    {
        parent::__construct("leave", $owner);
        $this->setDescription("");

        $this->plugin = $owner;
    }

    public function execute(CommandSender $player, string $commandLabel, array $args): bool
    {
        if ($player instanceof Player) {
            $member = $this->plugin->getPlayerManager()->getPlayer($player);

            if ($member->getDuel() === null) {
                $player->sendMessage("You are not in a queue!");
                return false;
            }

            $duel = $member->getDuel();
            if ($duel->getStatus() === false) {
                $player->sendMessage("You are in an active game!");
                return false;
            }

            $this->plugin->getDuelManager()->deleteDuel($duel->getId());
            $member->setDuel(null);
            $player->sendMessage("You left the queue");

            return true;
        }

        return false;
    }
}







