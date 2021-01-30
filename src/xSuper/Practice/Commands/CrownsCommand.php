<?php

namespace xSuper\Practice\Commands;

use pocketmine\{command\CommandSender, command\PluginCommand, Player, Server};
use xSuper\Practice\Practice;

class CrownsCommand extends PluginCommand
{
    /** @var Practice */
    private $plugin;

    public function __construct(Practice $owner)
    {
        parent::__construct("crowns", $owner);
        $this->setPermission("crowns.command");
        $this->setDescription("");

        $this->plugin = $owner;
    }

    public function execute(CommandSender $player, string $commandLabel, array $args): bool
    {
        if(!$this->testPermission($player)) return false;

        $player = $this->plugin->getPlayerManager()->getPlayerByName($args[0]);

        $player->addCrowns($args[1]);

        return true;
    }
}





