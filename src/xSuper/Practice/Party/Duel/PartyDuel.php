<?php

declare(strict_types=1);

namespace xSuper\Practice\Party\Duel;

use pocketmine\level\format\io\BaseLevelProvider;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use xSuper\Practice\Party\Party;
use xSuper\Practice\Practice;

class PartyDuel
{
    /** @var string */
    private $id;
    /** @var Party */
    private $party;
    /** @var string */
    private $type;
    /** @var string */
    private $map;

    /** @var array */
    private $alive;

    /** @var int */
    private $time = 0;

    /** @var Task */
    private $task = null;

    /** @var Practice */
    private $plugin;

    public function __construct(string $id, Party $party, string $type, string $map, Practice $plugin)
    {
        $this->id = $id;
        $this->party = $party;
        $this->map = $map;
        $this->type = $type;
        $this->alive = $party->getMembers();

        $this->plugin = $plugin;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getParty(): Party
    {
        return $this->party;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function init(): void
    {
        $this->plugin->getMapGenerator()->genMap($this->map, $this->id, $this->plugin->getServer()->getDataPath() . "/worlds/" . "game_" . $this->id);
        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function (): void {
            $this->plugin->getServer()->loadLevel("game_" . $this->id);

            $provider = $this->plugin->getServer()->getLevelByName("game_" . $this->id)->getProvider();

            if (!$provider instanceof BaseLevelProvider) return;

            $provider->getLevelData()->setString("LevelName", "game_" . $this->id);
            $provider->saveLevelData();

            $this->plugin->getServer()->unloadLevel($this->plugin->getServer()->getLevelByName("game_" . $this->id));
            $this->plugin->getServer()->loadLevel("game_" . $this->id);
            $this->plugin->getServer()->getLevelByName("game_" . $this->id)->setDifficulty(2);
        }), 1);


        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function (): void {
            $i = 0;
            foreach ($this->party->getMembers() as $player) {
                $player = $this->plugin->getServer()->getPlayer($player);
                if ($player instanceof Player && $player->isOnline()) {
                    $i++;

                    if ($i % 2 === 0) {
                        $t = 1;
                    } else {
                        $t = 2;
                    }

                    $player->setImmobile(true);
                    $player->teleport(new Position($this->plugin->getConfig()->getNested("maps." . $this->map . ".spawn." . $t . ".x"), $this->plugin->getConfig()->getNested("maps." . $this->map . ".spawn." . $t . ".y"), $this->plugin->getConfig()->getNested("maps." . $this->map . ".spawn." . $t . ".z"), $this->plugin->getServer()->getLevelByName("game_" . $this->id)));
                    $this->plugin->getKitsAPI()->getKit($this->type, $player);
                }
            }

            $this->startCountdown();
        }), 2);
    }

    public function getAlive(): array
    {
        return $this->alive;
    }

    public function removeAlive(string $player): void
    {
        $key = array_search($player, $this->alive);
        unset($this->alive[$key]);
    }

    public function startCountdown(): void
    {
        $this->time = 6;

        $task = new ClosureTask(function (): void {
            if (count($this->party->getMembers()) <= 1) {
                $this->endTask();
                foreach ($this->party->getMembers() as $player) {
                    $this->plugin->getPartyAPI()->endDuel($this, $player);
                }
            }

            $this->time = $this->time - 1;

            if ($this->time === 0) {
                $this->endTask();
                $this->start();
                foreach ($this->party->getMembers() as $player) {
                    $player = $this->plugin->getServer()->getPlayer($player);
                    if ($player instanceof Player && $player->isOnline()) {
                        $player->sendTitle(" ");
                    }
                }

                return;
            }

            foreach ($this->party->getMembers() as $player) {
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

    public function start(): void
    {
        foreach ($this->party->getMembers() as $player) {
            $player = $this->plugin->getServer()->getPlayer($player);
            if ($player instanceof Player && $player->isOnline()) {
                $player->setImmobile(false);
                $this->plugin->getPlayerManager()->getPlayer($player)->setCanDamage(true);
            }
        }
    }
}
