<?php

namespace xSuper\Practice\Commands;

use pocketmine\{command\CommandSender, command\PluginCommand, Player, Server};
use xSuper\Practice\Practice;

class EloCommand extends PluginCommand
{
    /** @var Practice */
    private $plugin;

    public function __construct(Practice $owner)
    {
        parent::__construct("elo", $owner);
        $this->setPermission("elo.command");
        $this->setDescription("");

        $this->plugin = $owner;
    }

    public function execute(CommandSender $player, string $commandLabel, array $args): bool
    {
        if(!$this->testPermission($player)) return false;

        $player = $this->plugin->getPlayerManager()->getPlayerByName($args[0]);

        echo($player->getElo() . "\n");
        $player->addElo($args[1]);

        return true;
    }
}






