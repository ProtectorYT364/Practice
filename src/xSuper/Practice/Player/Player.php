<?php

declare(strict_types=1);

namespace xSuper\Practice\Player;

use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\utils\UUID;
use xSuper\Practice\BotDuel\BotDuel;
use xSuper\Practice\Duel\Duel;
use xSuper\Practice\Party\Duel\PartyDuel;
use xSuper\Practice\Party\Party;
use xSuper\Practice\Practice;

class PracticePlayer
{
    /** @var UUID */
    private $uuid;
    /** @var string */
    private $username;
    /** @var int */
    private $elo;
    /** @car int */
    private $crowns;
    /** @var string */
    private $rank;

    /** @var bool */
    private $canDamage = false;
    /** @var ?Duel */
    private $duel = null;
    /** @var ?BotDuel */
    private $botDuel = null;
    /** @var int */
    private $epearlCD = 0;
    /** @var ?Task */
    private $task = null;
    /** @var int */
    private $killStreak = 0;
    /** @var ?Party */
    private $party = null;
    /** @var ?PartyDuel */
    private $partyDuel = null;
    /** @var bool */
    private $ffa = false;

    // Hack for custom bot duels
    /** @var float */
    public $botSpeed = 0.55;

    /** @var float */
    public $botHealth = 20;

    /** @var float */
    public $botDamage = 8;

    /** @var float */
    public $botReach = 3;

    /** @var float */
    public $botAccuracy = 40;

    /** @var float */
    public $botAttackCooldown = 8;

    /** @var float */
    public $botLowReach = 0.5;

    /** @var float */
    public $botSafeDistance = 2;

    /** @var float */
    public $botPotTicks = 120;

    /** @var float */
    public $botPotChance = 990;

    /** @var ?Task */
    private $invTask = null;
    /** @var array */
    private $invites = [];


    /** @var Practice */
    private $plugin;

    public function __construct(UUID $uuid, string $username, int $elo, int $crowns, string $rank, Practice $plugin)
    {
        $this->uuid = $uuid;
        $this->username = $username;
        $this->elo = $elo;
        $this->crowns = $crowns;
        $this->rank = $rank;

        $this->plugin = $plugin;
    }

    public function getUuid(): UUID
    {
        return $this->uuid;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
        $this->update();
    }

    public function getRank(): string
    {
        return $this->rank;
    }

    public function setRank(string $rank): void
    {
        $this->rank = $rank;
        $this->update();
    }

    public function getElo(): int
    {
        return $this->elo;
    }

    public function removeElo(int $amount): void
    {
        $this->elo = $this->elo - $amount;
        $this->update();
    }

    public function addElo(int $amount): void
    {
        $this->elo = $this->elo + $amount;
        $this->update();
    }

    public function getCrowns(): int
    {
        return $this->crowns;
    }

    public function removeCrowns(int $amount): void
    {
        $this->crowns = $this->crowns - $amount;
        $this->update();
    }

    public function getKS(): int
    {
        return $this->killStreak;
    }

    public function addKS(int $amount): void
    {
        $this->killStreak = $this->killStreak + $amount;
    }

    public function removeKS(): void
    {
        $this->killStreak = 0;
    }

    public function addCrowns(int $amount): void
    {
        $this->crowns = $this->crowns + $amount;
        $this->update();
    }

    public function canDamage(): bool
    {
        return $this->canDamage;
    }

    public function setCanDamage(bool $value): void
    {
        $this->canDamage = $value;
    }

    public function getDuel(): ?Duel
    {
        return $this->duel;
    }

    public function setDuel(?Duel $duel): void
    {
        $this->duel = $duel;
    }

    public function getPartyDuel(): ?PartyDuel
    {
        return $this->partyDuel;
    }

    public function setPartyDuel(?PartyDuel $duel): void
    {
        $this->partyDuel = $duel;
    }

    public function getBotDuel(): ?BotDuel
    {
        return $this->botDuel;
    }

    public function setBotDuel(?BotDuel $duel): void
    {
        $this->botDuel = $duel;
    }

    public function getRankedName(): string
    {
        $crowns = $this->crowns;
        echo($crowns . "\n");
        if ($crowns < 200) return "Unranked";
        if ($crowns >= 200 && $crowns < 400) return "Rookie I";
        if ($crowns >= 400 && $crowns < 600) return "Rookie II";
        if ($crowns >= 600 && $crowns < 800) return "Rookie III";
        if ($crowns >= 800 && $crowns < 1000) return "Rookie IV";
        if ($crowns >= 1000 && $crowns < 1200) return "Rookie V";
        if ($crowns >= 1200 && $crowns < 1400) return "Veteran I";
        if ($crowns >= 1400 && $crowns < 1600) return "Veteran II";
        if ($crowns >= 1600 && $crowns < 1800) return "Veteran III";
        if ($crowns >= 1800 && $crowns < 2000) return "Veteran IV";
        if ($crowns >= 2000 && $crowns < 2200) return "Veteran V";
        if ($crowns >= 2200 && $crowns < 2400) return "Elite I";
        if ($crowns >= 2400 && $crowns < 2600) return "Elite II";
        if ($crowns >= 2600 && $crowns < 2800) return "Elite III";
        if ($crowns >= 2800 && $crowns < 3000) return "Elite IV";
        if ($crowns >= 3000 && $crowns < 3300) return "Elite V";
        if ($crowns >= 3300 && $crowns < 3600) return "Pro I";
        if ($crowns >= 3600 && $crowns < 3900) return "Pro II";
        if ($crowns >= 3900 && $crowns < 4200) return "Pro III";
        if ($crowns >= 4200 && $crowns < 4500) return "Pro IV";
        if ($crowns >= 4500 && $crowns < 4900) return "Pro V";
        if ($crowns >= 4900 && $crowns < 5300) return "Master I";
        if ($crowns >= 5300 && $crowns < 5700) return "Master II";
        if ($crowns >= 5700 && $crowns < 6100) return "Master III";
        if ($crowns >= 6100 && $crowns < 6500) return "Master IV";
        if ($crowns >= 6500 && $crowns < 6900) return "Master V";
        return "Legendary";
    }

    public function startCD(): void
    {
        $this->epearlCD = 10;

        $task = new ClosureTask(function (): void {
            $this->epearlCD = $this->epearlCD - 1;
            $player = $this->plugin->getServer()->getPlayerByUUID($this->uuid);
            if ($player instanceof Player && $player->isOnline()) {
                $player->setXpLevel($this->epearlCD);
                $player->setXpProgress($this->epearlCD / 10);
            }
            if ($this->epearlCD <= 0) {
                $this->endTask();
                return;
            }
        });

        $this->plugin->getScheduler()->scheduleRepeatingTask($task, 20);
        $this->task = $task;
    }

    public function endTask(): void
    {
        if ($this->task === null) return;
        $this->plugin->getScheduler()->cancelTask($this->task->getTaskId());
    }

    public function getPearlCD(): int
    {
        return $this->epearlCD;
    }

    public function initInvite(Party $party): void
    {
        $this->invites[$party->getOwner()] = 60;

        $task = new ClosureTask(function () use ($party): void {
            $this->invites[$party->getOwner()] = $this->invites[$party->getOwner()] - 1;

            if ($this->invites[$party->getOwner()] === 0) {
                $this->plugin->getPartyAPI()->removeInvite($this->plugin->getServer()->getPlayer($this->getUsername()), $party);
                $this->endInvTask();
            }
        });

        $this->plugin->getScheduler()->scheduleRepeatingTask($task, 20);
        $this->invTask = $task;
    }

    public function endInvTask(): void
    {
        if ($this->invTask === null) return;
        $this->plugin->getScheduler()->cancelTask($this->invTask->getTaskId());
        $this->invTask = null;
    }

    public function getInvites(): ?array
    {
        return $this->invites;
    }

    public function setInvites(array $invites): void
    {
        $this->invites = $invites;
    }

    public function getParty(): ?Party
    {
        return $this->party;
    }

    public function setParty(?Party $party): void
    {
        $this->party = $party;
    }

    public function getFFA(): bool
    {
        return $this->ffa;
    }

    public function setFFA(bool $value): void
    {
        $this->ffa = $value;
    }

    public function update(): void
    {
        $this->plugin->getDatabase()->executeChange("practice.players.update", [
            "uuid" => $this->uuid->toString(),
            "username" => $this->username,
            "elo" => $this->elo,
            "crowns" => $this->crowns,
            "rank" => $this->rank
        ]);
    }
}

