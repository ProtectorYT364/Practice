<?php

declare(strict_types=1);

namespace xSuper\Practice\BotDuel;

use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\level\format\io\BaseLevelProvider;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use xSuper\Practice\Practice;

class BotDuel
{
    /** @var string */
    private $id;
    /** @var string */
    private $player;
    /** @var string */
    private $type;

    /** @var Human */
    private $bot = null;

    /** @var Practice */
    private $plugin;

    /** @var Task */
    private $task = null;

    /** @var int */
    private $time = 0;

    /**
     * @param string $id
     * @param string $player
     * @param string $type
     * @param Practice $plugin
     */

    public function __construct(string $id, string $player, string $type, Practice $plugin)
    {
        $this->id = $id;
        $this->player = $player;
        $this->type = $type;

        $this->plugin = $plugin;
        $this->init();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPlayer(): string
    {
        return $this->player;
    }

    public function getTime(): string
    {
        if ($this->time === 0) return "N/A";
        return (string) $this->time;
    }

    public function init(): void
    {
        $this->plugin->getMapGenerator()->genMap("test", $this->id, $this->plugin->getServer()->getDataPath() . "/worlds/" . "game_" . $this->id);
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
        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function (): void {
            $player = $this->plugin->getServer()->getPlayer($this->player);
            $this->plugin->getPlayerManager()->getPlayer($player)->setBotDuel($this);
            $level = $this->plugin->getServer()->getLevelByName("game_" . $this->id);
            $vec = new Vector3($this->plugin->getConfig()->getNested("maps.test.spawn.2.x"), $this->plugin->getConfig()->getNested("maps.test.spawn.2.y"), $this->plugin->getConfig()->getNested("maps.test.spawn.2.z"));
            $nbt = Entity::createBaseNBT($vec, null, 2, 2);
            if ($this->type === "easy") {
                $bot = new Bot($level, $nbt, ["speed" => 0.55, "safeDistance" => 2, "reach" => 3, "accuracy" => 40, "attackCooldown" => 8, "potChance" => 990, "potTicks" => 120, "lowReach" => 0.5, "health" => 20, "damage" => 8], "Easy", $this->plugin);
            } else if ($this->type === "medium") {
                $bot = new Bot($level, $nbt, ["speed" => 0.60, "safeDistance" => 2.5, "reach" => 3.5, "accuracy" => 50, "attackCooldown" => 6, "potChance" => 973, "potTicks" => 100, "lowReach" => 0.6, "health" => 20, "damage" => 8], "Medium", $this->plugin);
            } else if ($this->type === "hard") {
                $bot = new Bot($level, $nbt, ["speed" => 0.65, "safeDistance" => 3, "reach" => 3.8, "accuracy" => 70, "attackCooldown" => 4, "potChance" => 970, "potTicks" => 80, "lowReach" => 0.7, "health" => 20, "damage" => 8], "Hard", $this->plugin);
            } else if ($this->type === "hacker") {
                $bot = new Bot($level, $nbt, ["speed" => 0.70, "safeDistance" => 3.5, "reach" => 4.5, "accuracy" => 90, "attackCooldown" => 1.3, "potChance" => 950, "potTicks" => 60, "lowReach" => 0.8, "health" => 20, "damage" => 7.5], "Hacker", $this->plugin);
            } else if ($this->type === "custom") {
                $member = $this->plugin->getPlayerManager()->getPlayer($player);
                $bot = new Bot($level, $nbt, ["speed" => $member->botSpeed, "safeDistance" => $member->botSafeDistance, "reach" => $member->botReach, "accuracy" => $member->botAccuracy, "attackCooldown" => $member->botAttackCooldown, "potChance" => $member->botPotChance, "potTicks" => $member->botPotTicks, "lowReach" => $member->botLowReach, "health" => $member->botHealth, "damage" => $member->botDamage], "Custom", $this->plugin);
            }

            $this->bot = $bot;
            $bot->setDuel($this);
            $bot->setNameTagAlwaysVisible(true);
            $bot->spawnToAll();
            $bot->setImmobile(true);
            $bot->setTarget($this->plugin->getServer()->getPlayer($this->player));
            $bot->teleport(new Position($this->plugin->getConfig()->getNested("maps.test.spawn.2.x"), $this->plugin->getConfig()->getNested("maps.test.spawn.2.y"), $this->plugin->getConfig()->getNested("maps.test.spawn.2.z"), $level));

            $player->teleport(new Position($this->plugin->getConfig()->getNested("maps.test.spawn.1.x"), $this->plugin->getConfig()->getNested("maps.test.spawn.1.y"), $this->plugin->getConfig()->getNested("maps.test.spawn.1.z"), $level));
            $player->setImmobile(true);
            $this->plugin->getKitsAPI()->getKit("nodebuff", $player);
            $this->plugin->getKitsAPI()->getKit("nodebuff", $bot);
            $this->startCountdown();
        }), 2);
    }

    public function startCountdown(): void
    {
        $this->time = 6;

        $task = new ClosureTask(function (): void {
            $this->time = $this->time - 1;

            if ($this->time === 0) {
                $this->endTask();
                $player = $this->plugin->getServer()->getPlayer($this->player);
                if ($player instanceof Player && $player->isOnline()) {
                    $player->sendTitle(" ");
                    $player->setImmobile(false);
                    $this->bot->setImmobile(false);
                    $this->plugin->getPlayerManager()->getPlayer($player)->setCanDamage(true);
                }

                return;
            }

            $player = $this->plugin->getServer()->getPlayer($this->player);
            if ($player instanceof Player && $player->isOnline()) {
                $player->sendTitle(TextFormat::GREEN . $this->time);
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
}

