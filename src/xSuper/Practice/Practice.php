<?php

declare(strict_types=1);

namespace xSuper\Practice;

use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use xSuper\Practice\BotDuel\Bot;
use xSuper\Practice\BotDuel\BotDuelAPI;
use xSuper\Practice\BotDuel\BotDuelManager;
use xSuper\Practice\BotDuel\EasyBot;
use xSuper\Practice\BotDuel\HardBot;
use xSuper\Practice\BotDuel\MediumBot;
use xSuper\Practice\Commands\CrownsCommand;
use xSuper\Practice\Commands\DuelCommand;
use xSuper\Practice\Commands\EloCommand;
use xSuper\Practice\Commands\LeaveCommand;
use xSuper\Practice\Commands\PartyCommand;
use xSuper\Practice\Commands\RankCommand;
use xSuper\Practice\Duel\DuelAPI;
use xSuper\Practice\Duel\DuelManager;
use xSuper\Practice\FFA\FFA;
use xSuper\Practice\Generators\MapGenerator;
use xSuper\Practice\Kits\KitsAPI;
use xSuper\Practice\Party\Duel\PartyDuel;
use xSuper\Practice\Party\Duel\PartyDuelManager;
use xSuper\Practice\Party\PartyAPI;
use xSuper\Practice\Party\PartyManager;
use xSuper\Practice\Player\PlayerManager;
use xSuper\Practice\Tasks\UpdateScoreboardsTask;

class Practice extends PluginBase
{
    /** @var DataConnector */
    private $database;
    /** @var DuelManager */
    private $duelManager;
    /** @var DuelAPI */
    private $duelAPI;
    /** @var KitsAPI */
    private $kitsAPI;
    /** @var MapGenerator */
    private $mapGenerator;
    /** @var PlayerManager */
    private $playerManager;
    /** @var BotDuelManager */
    private $botDuelManager;
    /** @var BotDuelAPI */
    private $botDuelAPI;
    /** @var PartyManager */
    private $partyManager;
    /** @var PartyAPI */
    private $partyAPI;
    /** @var PartyDuelManager */
    private $partyDuelManager;
    /** @var FFA */
    private $ffa;
    /** @var String */
    public static $objectiveName = "practice";
    public static $duelObjectiveName = "duel";
    /** @var int */
    public $i = 0;

    public function onEnable(): void
    {
        if (!is_dir($this->getServer()->getDataPath() . "/plugin_data/Practice/maps")) {
            mkdir($this->getServer()->getDataPath() . "/plugin_data/Practice/maps");
        }

        foreach (
            [
                "libasynql" => libasynql::class
            ] as $virion => $class
        ) {
            if (!class_exists($class)) {
                $this->getLogger()->error($virion . " virion not found.");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
            }
        }

        $this->initDatabase();
        Entity::registerEntity(Bot::class, true);

        $this->duelAPI = new DuelAPI($this);
        $this->kitsAPI = new KitsAPI($this);

        $this->duelManager = new DuelManager($this);
        $this->mapGenerator = new MapGenerator($this);
        $this->playerManager = new PlayerManager($this);
        $this->botDuelManager = new BotDuelManager($this);
        $this->botDuelAPI = new BotDuelAPI($this);
        $this->partyManager = new PartyManager($this);
        $this->partyAPI = new PartyAPI($this);
        $this->partyDuelManager = new PartyDuelManager($this);
        $this->ffa = new FFA($this);

        $this->ffa->loadWorlds();

        $this->getScheduler()->scheduleRepeatingTask(new UpdateScoreboardsTask($this), 20);

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getCommandMap()->register("duel", new DuelCommand($this));
        $this->getServer()->getCommandMap()->register("crowns", new CrownsCommand($this));
        $this->getServer()->getCommandMap()->register("elo", new EloCommand($this));
        $this->getServer()->getCommandMap()->register("rank", new RankCommand($this));
        $this->getServer()->getCommandMap()->register("leave", new LeaveCommand($this));
        $this->getServer()->getCommandMap()->register("party", new PartyCommand($this));

        $this->saveDefaultConfig();
    }

    public function onDisable(): void
    {
        if ($this->database !== null) {
            $this->database->waitAll();
            $this->database->close();
        }
    }

    private function initDatabase(): void
    {
        $this->database = libasynql::create($this, $this->getConfig()->get("database"), [
            "sqlite" => "sqlite.sql",
            "mysql" => "mysql.sql"
        ]);

        $this->database->executeGeneric("practice.players.init");
        $this->database->waitAll();
    }

    public function getDatabase(): DataConnector
    {
        return $this->database;
    }

    public function getDuelAPI(): DuelAPI
    {
        return $this->duelAPI;
    }

    public function getKitsAPI(): KitsAPI
    {
        return $this->kitsAPI;
    }

    public function getDuelManager(): DuelManager
    {
        return $this->duelManager;
    }

    public function getMapGenerator(): MapGenerator
    {
        return $this->mapGenerator;
    }

    public function getPlayerManager(): PlayerManager
    {
        return $this->playerManager;
    }

    public function getBotDuelManager(): BotDuelManager
    {
        return $this->botDuelManager;
    }

    public function getBotDuelAPI(): BotDuelAPI
    {
        return $this->botDuelAPI;
    }

    public function getPartyManager(): PartyManager
    {
        return $this->partyManager;
    }

    public function getPartyAPI(): PartyAPI
    {
        return $this->partyAPI;
    }

    public function getPartyDuelManager(): PartyDuelManager
    {
        return $this->partyDuelManager;
    }

    public function getFFA(): FFA
    {
        return $this->ffa;
    }
}
