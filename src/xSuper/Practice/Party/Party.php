<?php

declare(strict_types=1);

namespace xSuper\Practice\Party;

use pocketmine\Player;
use xSuper\Practice\Practice;

class Party
{
    /** @var string */
    private $id;
    /** @var string */
    private $owner;
    /** @var array */
    private $members;

    /** @var array */
    private $invited = [];

    /** @var Practice */
    private $plugin;

    public function __construct(string $id, string $owner, array $members, Practice $plugin)
    {
        $this->id = $id;
        $this->owner = $owner;
        $this->members = $members;

        $this->plugin = $plugin;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOwner(): string
    {
        return $this->owner;
    }

    public function setOwner(Player $owner): void
    {
        $this->owner = $owner->getName();
    }

    public function getMembers(): array
    {
        return $this->members;
    }

    public function addMember(Player $player): void
    {
        $this->members[] = $player->getName();
    }

    public function removeMember(Player $player): void
    {
        $key = array_search($player->getName(), $this->members);
        unset($this->members[$key]);
    }

    public function invitePlayer(Player $player): void
    {
        $this->invited[] = $player->getName();
    }

    public function revokeInvite(Player $player): void
    {
        $key = array_search($player->getName(), $this->invited);
        unset($this->invited[$key]);
    }
}

