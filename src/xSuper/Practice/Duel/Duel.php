<?php

declare(strict_types=1);

namespace xSuper\Practice\Duel;

use pocketmine\level\format\io\BaseLevelProvider;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use xSuper\Practice\Libs\ScoreAPI;
use xSuper\Practice\Practice;

class Duel
{
    /** @var string */
    private $id;
    /** @var array */
    private $players;
    /** @var bool */
    private $open = true;
    /** @var string */
    private $type;
    /** @var bool */
    private $ranked;
    /** @var string */
    private $map;

    /** @var Practice */
    private $plugin;

    /** @var Task */
    private $task = null;

    /** @var int */
    private $time = 0;

    /**
     * @param string $id
     * @param array $players
     * @param string $type
     * @param bool $ranked
     * @param Practice $plugin
     */

    public function __construct(string $id, array $players, string $type, bool $ranked, Practice $plugin, string $map)
    {
        $this->id = $id;
        $this->players = $players;
        $this->type = $type;
        $this->ranked = $ranked;
        $this->map = $map;

        $this->plugin = $plugin;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function addPlayer(string $player): void
    {
        $this->players[] = $player;
    }

    public function getPlayers(): array
    {
        return $this->players;
    }

    public function removePlayer(string $player): void
    {
        $key = array_search($player, $this->players);
        unset($this->players[$key]);
    }

    public function getStatus(): bool
    {
        return $this->open;
    }

    public function setStatus(bool $status): void
    {
        $this->open = $status;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMap(): string
    {
        return $this->map;
    }

    public function getRanked(): bool
    {
        return $this->ranked;
    }

    public function getTime(): string
    {
        if ($this->time === 0) return "N/A";
        return (string) $this->time;
    }

    public function init(): void
    {
        $this->plugin->getMapGenerator()->genMap($this->map, $this->id, $this->plugin->getServer()->getDataPath() . "/worlds/" . "game_" . $this->id);
        $task = new ClosureTask(function (): void {
                $this->plugin->getServer()->loadLevel("game_" . $this->id);

                $provider = $this->plugin->getServer()->getLevelByName("game_" . $this->id)->getProvider();

                if (!$provider instanceof BaseLevelProvider) return;

                $provider->getLevelData()->setString("LevelName", "game_" . $this->id);
                $provider->saveLevelData();

                $this->plugin->getServer()->unloadLevel($this->plugin->getServer()->getLevelByName("game_" . $this->id));
                $this->plugin->getServer()->loadLevel("game_" . $this->id);
                $this->plugin->getServer()->getLevelByName("game_" . $this->id)->setDifficulty(2);
        });

        $this->plugin->getScheduler()->scheduleDelayedTask($task, 1);
        $this->setStatus(false);
        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function (): void {
            $i = 0;
            foreach ($this->players as $player) {
                $player = $this->plugin->getServer()->getPlayer($player);
                if ($player instanceof Player && $player->isOnline()) {
                    $i++;
                    $player->setImmobile(true);
                    $player->teleport(new Position($this->plugin->getConfig()->getNested("maps." . $this->map . ".spawn." . $i . ".x"), $this->plugin->getConfig()->getNested("maps." . $this->map . ".spawn." . $i . ".y"), $this->plugin->getConfig()->getNested("maps." . $this->map . ".spawn." . $i . ".z"), $this->plugin->getServer()->getLevelByName("game_" . $this->id)));
                    $this->plugin->getKitsAPI()->getKit($this->type, $player);
                    $this->plugin->getPlayerManager()->getPlayer($player)->setCanDamage(true);
                }
            }

            $this->startCountdown();
        }), 2);
    }

    public function startCountdown(): void
    {
        $this->time = 6;

        $task = new ClosureTask(function (): void {
            if (count($this->players) <= 1) {
                $this->endTask();
                foreach ($this->players as $player) {
                    $this->plugin->getDuelAPI()->endDuel($this, $player);
                }
            }

            $this->time = $this->time - 1;

            if ($this->time === 0) {
                $this->endTask();
                $this->startDuel();
                foreach ($this->players as $player) {
                    $player = $this->plugin->getServer()->getPlayer($player);
                    if ($player instanceof Player && $player->isOnline()) {
                        $player->sendTitle(" ");
                    }
                }

                return;
            }

            foreach ($this->players as $player) {
                $player = $this->plugin->getServer()->getPlayer($player);
                if ($player instanceof Player && $player->isOnline()) {
                    $player->sendTitle(TextFormat::GREEN . $this->time);
                }
            }
        });

        $this->plugin->getScheduler()->scheduleRepeatingTask($task, 20);
        $this->task = $task;
    }

    public function endTask(): void
    {
        if ($this->task === null) return;
        $this->plugin->getScheduler()->cancelTask($this->task->getTaskId());
        $this->task = null;
    }

    public function startGame(): void
    {
        $this->time = 420;
        $task = new ClosureTask(function (): void
        {
            $this->time = $this->time - 1;

            if ($this->time <= 0) {
                $this->endTask();
                $this->plugin->getDuelAPI()->endDuel($this, "time");
                return;
            }
        });

        $this->plugin->getScheduler()->scheduleRepeatingTask($task, 20);
        $this->task = $task;
    }

    public function startDuel(): void
    {
        foreach ($this->players as $player) {
            $player = $this->plugin->getServer()->getPlayer($player);
            if ($player instanceof Player && $player->isOnline()) {
                $player->setImmobile(false);
            }
        }

        $this->startGame();
    }
}
