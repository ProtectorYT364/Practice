<?php
declare(strict_types = 1);

namespace xSuper\Practice\Libs;

use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\Player;
use pocketmine\Server;

class ScoreAPI{
	
	/** @var string */
	private const objectiveName = "objective";
	/** @var string */
	private const criteriaName = "dummy";
	/** @var int */
	private const MIN_LINES = 1;
	/** @var int */
	private const MAX_LINES = 15;
	/** @var int */
	public const SORT_ASCENDING = 0;
	/** @var int */
	public const SORT_DESCENDING = 1;
	/** @var string */
	public const SLOT_LIST = "list";
	/** @var string */
	public const SLOT_SIDEBAR = "sidebar";
	/** @var string */
	public const SLOT_BELOW_NAME = "belowname";
	/** @var array */
	private static $scorelist = [];
	/** @var object */
	private static $scoreboards = [];
	
	/**
	 * Adds a Scoreboard to the player if he doesn't have one.
	 * Can also be used to update a scoreboard.
	 *
	 * @param Player $player
	 * @param string $displayName
     * @param string $objectiveName
	 * @param int    $slotOrder
	 * @param string $displaySlot
	 */
	public static function setScore(string $objectiveName, string $displayName): void{
		$pk = new SetDisplayObjectivePacket();
		$pk->displaySlot = self::SLOT_SIDEBAR;
		$pk->objectiveName = $objectiveName;
		$pk->displayName = $displayName;
		$pk->criteriaName = self::criteriaName;
		$pk->sortOrder = self::SORT_ASCENDING;
		self::$scoreboards[$objectiveName] = $pk;
	}

	public static function updateName(string $objectiveName, string $newDisplayName){
	    if (!isset(self::$scoreboards[$objectiveName])){
	        Server::getInstance()->getLogger()->error("The scoreboard with the objective name $objectiveName does not exist");
	        return;
        }

	    $pk = self::$scoreboards[$objectiveName];
        $pk->displaySlot = self::SLOT_SIDEBAR;
        $pk->objectiveName = $objectiveName;
        $pk->displayName = $newDisplayName;
        $pk->criteriaName = self::criteriaName;
        $pk->sortOrder = self::SORT_ASCENDING;
	    self::$scoreboards[$objectiveName] = $pk;
    }


	/**
	 * Removes a scoreboard from the player specified.
	 *
	 * @param Player $player
     * @param string $objectiveName
	 */
	public static function removeScore(string $objectiveName, Player $player): void{
		$pk = new RemoveObjectivePacket();
		$pk->objectiveName = $objectiveName;
		$player->sendDataPacket($pk);
		
		if(isset(self::$scorelist[($player->getName())])){
			unset(self::$scorelist[$player->getName()]);
		}
	}

    /**
     * Adds a scoreboard to the player specified.
     *
     * @param Player $player
     * @param string $objectiveName
     */

	public static function sendScore(string $objectiveName, Player $player){
	    if (!isset(self::$scoreboards[$objectiveName])){
	        Server::getInstance()->getLogger()->error("Can not find the scoreboard $objectiveName");
	        return;
        }

        if (isset(self::$scorelist[($player->getName())])){
            $pk = new RemoveObjectivePacket();
            $pk->objectiveName = self::$scorelist[$player->getName()];
            $player->sendDataPacket($pk);
        }

        $player->sendDataPacket(self::$scoreboards[$objectiveName]);

        if (!isset(self::$scorelist[($player->getName())])){
            self::$scorelist[$player->getName()] = $objectiveName;
        }
    }
	
	/**
	 * Returns an array consisting of a list of the players using scoreboard.
	 *
	 * @return array
	 */
	public static function getScoreboards(): array{
		return self::$scoreboards;
	}
	
	/**
	 * Returns true or false if a player has a scoreboard or not.
	 *
	 * @param Player $player
	 * @return bool
	 */
	public static function hasScore(Player $player): bool{
		return isset(self::$scorelist[$player->getName()]);
	}
	
	/**
	 * Set a message at the line specified to the players scoreboard.
	 *
	 * @param Player $player
     * @param string $objectiveName
	 * @param int    $line
	 * @param string $message
	 */
	public static function setScoreLine(string $objectiveName, Player $player, int $line, $message): void{
		if(!isset(self::$scorelist[$player->getName()])){
			Server::getInstance()->getLogger()->error("Cannot set a score to a player with no scoreboard");
			return;
		}
		if($line < self::MIN_LINES || $line > self::MAX_LINES){
			Server::getInstance()->getLogger()->error("Score must be between the value of " . self::MIN_LINES .  " to " . self::MAX_LINES . ".");
			Server::getInstance()->getLogger()->error($line . " is out of range");
			return;
		}
		
		$entry = new ScorePacketEntry();
		$entry->objectiveName = $objectiveName;
		$entry->type = $entry::TYPE_FAKE_PLAYER;
		$entry->customName = (string)$message;
		$entry->score = $line;
		$entry->scoreboardId = $line;

        $pk = new SetScorePacket();
        $pk->type = $pk::TYPE_REMOVE;
        $pk->entries[] = $entry;
        $player->sendDataPacket($pk);
		
		$pk = new SetScorePacket();
		$pk->type = $pk::TYPE_CHANGE;
		$pk->entries[] = $entry;
		$player->sendDataPacket($pk);
	}
}
