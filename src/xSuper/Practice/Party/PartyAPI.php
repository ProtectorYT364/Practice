<?php

declare(strict_types=1);

namespace xSuper\Practice\Party;

use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat as T;
use xSuper\Practice\Party\Duel\PartyDuel;
use xSuper\Practice\Practice;

class PartyAPI
{
    /** @var Practice */
    private $plugin;

    public function __construct(Practice $plugin)
    {
        $this->plugin = $plugin;
    }

    public function invitePlayer(Player $player, Party $party): void
    {
        $this->plugin->getPlayerManager()->getPlayer($player)->initInvite($party);
        $party->invitePlayer($player);

        $member = $this->plugin->getPlayerManager()->getPlayer($player);

        foreach ($member->getInvites() as $invite) {
            if ($party->getOwner() === array_search($invite, $member->getInvites())) {
                return;
            }
        }

        $player->sendMessage("You have been invited to " . $party->getOwner() . "'s party!");
    }

    public function removeInvite(Player $player, Party $party): void
    {
        if (!$player->isOnline()) return;

        $member = $this->plugin->getPlayerManager()->getPlayer($player);
        $invites = $member->getInvites();

        unset($invites[$party->getOwner()]);
        $member->setInvites($invites);

        $party->revokeInvite($player);
        $player->sendMessage("The party invite from " . $party->getOwner() . " has expired!");
    }

    public function acceptInvite(Player $player, Party $party): void
    {
        $member = $this->plugin->getPlayerManager()->getPlayer($player);

        if ($this->plugin->getPartyManager()->getParty($party->getId()) === null) {
            $player->sendMessage("That party no longer exists!");
            return;
        }

        $member->endInvTask();
        $this->removeInvite($player, $party);
        $party->revokeInvite($player);
        $party->addMember($player);
        $member->setParty($party);
        $player->sendMessage("You joined " . $party->getOwner() . "'s party!");
        foreach ($party->getMembers() as $member) {
            if ($member !== $player->getName()) {
                $this->plugin->getServer()->getPlayer($member)->sendMessage($player->getName() . " has joined the party!");
            }
        }
    }

    public function leaveParty(Player $player, Party $party): void
    {
        $member = $this->plugin->getPlayerManager()->getPlayer($player);

        if ($member->getPartyDuel() !== null) {
            $this->eliminatePlayer($member->getPartyDuel(), $player, false);
        }

        $member->setParty(null);
        $party->removeMember($player);
        foreach ($party->getMembers() as $member) {
            $this->plugin->getServer()->getPlayer($member)->sendMessage($player->getName() . " has left the party!");
        }
    }

    public function endDuel(PartyDuel $duel, string $winner = null): void
    {

        $this->plugin->getPartyDuelManager()->deleteDuel($duel->getId());
        $duel->endTask();
        $this->plugin->getMapGenerator()->deleteMap("game_" . $duel->getId());

        foreach($duel->getParty()->getMembers() as $player){
            $player = $this->plugin->getServer()->getPlayer($player);
            $this->resetPlayer($player);
            $this->plugin->getPlayerManager()->getPlayer($player)->setPartyDuel(null);
        }

        if (!$winner) return;

        if ($winner === "time") {
            return;
        }
    }

    public function eliminatePlayer(PartyDuel $duel, Player $player, bool $spec = true): void
    {
        $duel->removeAlive($player->getName());
        if ($spec) {
            $this->spectator($player);
        }

        $this->checkDuel($duel);
    }

    public function spectator(Player $player): void
    {
        $player->setGamemode(2);

        $player->setInvisible();
        $player->setAllowFlight(true);
        $player->setNameTagVisible(false);


        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->setHealth($player->getMaxHealth());
        $player->removeAllEffects();
        $player->getCursorInventory()->clearAll();
        $player->setAbsorption(0);
        $player->setGamemode(2);
        $player->extinguish();
        $this->plugin->getPlayerManager()->getPlayer($player)->setCanDamage(false);
    }

    public function resetPlayer(Player $player): void
    {
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->setHealth($player->getMaxHealth());
        $player->removeAllEffects();
        $player->getCursorInventory()->clearAll();
        $player->setAbsorption(0);
        $player->setGamemode(2);
        $player->extinguish();
        $player->setInvisible(false);
        $player->setAllowFlight(false);
        $player->setFlying(false);
        $player->setNameTagVisible(true);
        $this->plugin->getPlayerManager()->getPlayer($player)->setCanDamage(false);
        $duel = Item::get(Item::DIAMOND_SWORD)->setCustomName(T::RESET . T::RED . "Duels");
        $duel->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING)));
        $duel->getNamedTag()->setInt("duel", 1);
        $botDuel = Item::get(Item::ANVIL)->setCustomName(T::RESET . T::AQUA . "Bot Duels");
        $botDuel->getNamedTag()->setInt("botduel", 1);
        $party = Item::get(Item::BOOK)->setCustomName(T::RESET . T::LIGHT_PURPLE . "Partys");
        $party->getNamedTag()->setInt("party", 1);
        $player->getInventory()->setContents([
            0 => $duel,
            2 => $botDuel,
            4 => $party
        ]);
    }

    public function checkDuel(PartyDuel $duel): void
    {
        $players = $duel->getAlive();

        if (count($players) === 1) {
            $this->endDuel($duel, reset($players));
        } else if (count($players) <= 0) {
            $this->endDuel($duel);
        }
    }

    public function makeDuel(Party $party, string $type, string $map): void
    {
        $duel = $this->plugin->getPartyDuelManager()->createDuel($party, $type, $map);
        foreach ($party->getMembers() as $player) {
            $this->plugin->getPlayerManager()->getPlayerByName($player)->setPartyDuel($duel);
        }

        $duel->init();
    }
}


