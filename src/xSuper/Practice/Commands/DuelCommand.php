<?php

namespace xSuper\Practice\Commands;

use pocketmine\{command\CommandSender, command\PluginCommand, entity\Entity, math\Vector3, Player, Server};
use xSuper\Practice\BotDuel\EasyBot;
use xSuper\Practice\Practice;

class DuelCommand extends PluginCommand
{
    /** @var Practice */
    private $plugin;

    public function __construct(Practice $owner)
    {
        parent::__construct("duel", $owner);
        $this->setPermission("duel.command");
        $this->setDescription("");

        $this->plugin = $owner;
    }

    public function execute(CommandSender $player, string $commandLabel, array $args): bool
    {
        if(!$this->testPermission($player)) return false;

        $player = Server::getInstance()->getPlayer($player->getName());

        if ($player instanceof Player && $player->isOnline()) {
            $this->plugin->getBotDuelManager()->createDuel($player, $args[0]);
        }

        return true;
    }
}




