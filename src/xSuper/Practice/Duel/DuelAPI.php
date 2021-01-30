<?php

declare(strict_types=1);

namespace xSuper\Practice\Duel;

use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\types\Enchant;
use pocketmine\Player;
use pocketmine\utils\TextFormat as T;
use xSuper\Practice\Practice;

class DuelAPI
{
    /** @var Practice */
    private $plugin;

    public function __construct(Practice $plugin)
    {
        $this->plugin = $plugin;
    }

    public function findDuel(Player $player, string $type, bool $ranked, string $map): void
    {
        foreach ($this->plugin->getDuelManager()->getDuels() as $duel) {
            if ($duel->getStatus() === true && $duel->getType() === $type && $ranked === $duel->getRanked()) {
                if ($this->plugin->getPlayerManager()->getPlayer($player)->getDuel() !== null) return;
                if ($duel->getRanked() === true) {
                    foreach ($duel->getPlayers() as $player1) {
                        if (abs($this->plugin->getPlayerManager()->getPlayer($player)->getElo() - $this->plugin->getPlayerManager()->getPlayerByName($player1)->getElo()) <= 100) {
                            $this->joinPlayer($player, $duel);
                            return;
                        }
                    }
                } else {
                    $this->joinPlayer($player, $duel);
                }
                return;
            }
        }

        $this->makeDuel($player, $type, $ranked, $map);
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
	    $this->plugin->getPlayerManager()->getPlayer($player)->setCanDamage(false);
	    $duel = Item::get(Item::DIAMOND_SWORD)->setCustomName(T::RESET . T::RED . "Duels");
	    $duel->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING)));
	    $duel->getNamedTag()->setInt("duel", 1);
	    $botDuel = Item::get(Item::ANVIL)->setCustomName(T::RESET . T::AQUA . "Bot Duels");
	    $botDuel->getNamedTag()->setInt("botduel", 1);
	    $party = Item::get(Item::BOOK)->setCustomName(T::RESET . T::LIGHT_PURPLE . "Partys");
	    $party->getNamedTag()->setInt("party", 1);
	    $ffa = Item::get(Item::DIAMOND_AXE)->setCustomName(T::RESET . T::DARK_PURPLE . "FFA");
	    $ffa->getNamedTag()->setInt("ffa", 1);
	    $player->getInventory()->setContents([
	        0 => $duel,
            2 => $botDuel,
            4 => $ffa,
            6 => $party
        ]);
    }

    public function joinPlayer(Player $player, Duel $duel): void
    {
        $duel->addPlayer($player->getName());
        $this->plugin->getPlayerManager()->getPlayer($player)->setDuel($duel);
        $duel->init();
    }

    public function makeDuel(Player $player, string $type, bool $ranked, string $map): void
    {
        $duel = $this->plugin->getDuelManager()->createDuel($player, $type, $ranked, $map);
        $this->plugin->getPlayerManager()->getPlayer($player)->setDuel($duel);
    }

    public function checkDuel(Duel $duel): void
    {
        $players = $duel->getPlayers();
        if (count($players) === 1) {
            $this->endDuel($duel, reset($players));
        } else if (count($players) <= 0) {
            $this->endDuel($duel);
        }
    }

    public function eliminatePlayer(Duel $duel, Player $player): void
    {
        $duel->removePlayer($player->getName());
	    $this->resetPlayer($player);
        $this->plugin->getPlayerManager()->getPlayer($player)->setDuel(null);
        $this->plugin->getPlayerManager()->getPlayer($player)->addCrowns(5);
        if ($duel->getRanked() === true) {
            $this->plugin->getPlayerManager()->getPlayer($player)->removeElo(8);
        }
        $this->checkDuel($duel);
    }

    public function endDuel(Duel $duel, string $winner = null): void
    {

        $this->plugin->getDuelManager()->deleteDuel($duel->getId());
        $duel->endTask();
        $this->plugin->getMapGenerator()->deleteMap("game_" . $duel->getId());

	    foreach($duel->getPlayers() as $player){
	        $player = $this->plugin->getServer()->getPlayer($player);
            $this->resetPlayer($player);
	        $this->plugin->getPlayerManager()->getPlayer($player)->setDuel(null);
        }

	    if (!$winner) return;

	    if ($winner === "time") {
	        return;
        }

	    $winner = $this->plugin->getServer()->getPlayer($winner);
	    if ($winner !== null) {
	        $this->plugin->getPlayerManager()->getPlayer($winner)->addCrowns(15);
	        if ($duel->getRanked() === true) {
	            $this->plugin->getPlayerManager()->getPlayer($winner)->addElo(5);
            }
        }
    }
}
