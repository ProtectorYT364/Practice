<?php

namespace xSuper\Practice\Commands;

use pocketmine\{command\CommandSender, command\PluginCommand, Player};
use xSuper\Practice\Practice;

class PartyCommand extends PluginCommand
{
    /** @var Practice */
    private $plugin;

    public function __construct(Practice $owner)
    {
        parent::__construct("party", $owner);
        $this->setDescription("");

        $this->plugin = $owner;
    }

    public function execute(CommandSender $player, string $commandLabel, array $args): bool
    {
        if ($player instanceof Player) {
            if (!isset($args[0])) {
                $player->sendMessage("hepl msg for party");
                return false;
            }

            if (strtolower($args[0]) === "create") {
                $party = $this->plugin->getPartyManager()->createParty($player);
                $player->sendMessage("Party made");
                $this->plugin->getPlayerManager()->getPlayer($player)->setParty($party);
                return true;
            }

            if (strtolower($args[0]) === "accept") {
                if (!isset($args[1])) {
                    $player->sendMessage("Please specify who's party you want to join!");
                    return false;
                }

                $member = $this->plugin->getPlayerManager()->getPlayer($player);
                if (!isset($member->getInvites()[$args[1]])) {
                    $player->sendMessage("You have no party invites from that player!");
                    return false;
                }

                foreach ($this->plugin->getPartyManager()->getPartys() as $party) {
                    if ($party->getOwner() === $args[1]) {
                        $this->plugin->getPartyAPI()->acceptInvite($player, $party);
                        break;
                    }
                }
                return true;
            }

            if (strtolower($args[0]) === "invite") {
                $party = $this->plugin->getPlayerManager()->getPlayer($player)->getParty();
                if ($party === null) {
                    $player->sendMessage("You are not in a party!");
                    return false;
                }

                $player1 = $this->plugin->getServer()->getPlayer($args[1]);

                if ($player1 === null) {
                    $player->sendMessage("That player is not online!");
                    return false;
                }

                $this->plugin->getPartyAPI()->invitePlayer($player1, $party);
                $player->sendMessage($args[1] . " has been invited to the party!");
            }

            if (strtolower($args[0]) === "leave") {
                $party = $this->plugin->getPlayerManager()->getPlayer($player)->getParty();

                if ($party === null) {
                    $player->sendMessage("You are not in a party!");
                    return false;
                }

                $player->sendMessage("You left " . $party->getOwner() . "'s party!");
                $this->plugin->getPartyAPI()->leaveParty($player, $party);
            }

            if (strtolower($args[0]) === "duel") {
                $party = $this->plugin->getPlayerManager()->getPlayer($player)->getParty();

                if ($party === null) {
                    $player->sendMessage("You are not in a party!");
                    return false;
                }

                if (count($party->getMembers()) <= 1) {
                    $player->sendMessage("Your party does not have enough players!");
                    return false;
                }

                $this->plugin->getPartyAPI()->makeDuel($party, "nodebuff", "test");
            }

            if ($args[0] === "debug") {
                $party = $this->plugin->getPlayerManager()->getPlayer($player)->getParty();

                if ($party === null) {
                    $player->sendMessage("You are not in a party!");
                    return false;
                }

                print_r($party->getMembers());
            }
        }

        return false;
    }
}








