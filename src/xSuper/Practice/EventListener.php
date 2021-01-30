<?php

declare(strict_types=1);

namespace xSuper\Practice;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\block\Anvil;
use pocketmine\block\Bed;
use pocketmine\block\BrewingStand;
use pocketmine\block\BurningFurnace;
use pocketmine\block\Button;
use pocketmine\block\Chest;
use pocketmine\block\CraftingTable;
use pocketmine\block\Door;
use pocketmine\block\EnchantingTable;
use pocketmine\block\EnderChest;
use pocketmine\block\FenceGate;
use pocketmine\block\Furnace;
use pocketmine\block\IronDoor;
use pocketmine\block\IronTrapdoor;
use pocketmine\block\Lever;
use pocketmine\block\Trapdoor;
use pocketmine\block\TrappedChest;
use pocketmine\entity\Human;
use pocketmine\entity\Living;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat as T;
use pocketmine\Player;
use xSuper\Practice\BotDuel\Bot;

class EventListener implements Listener
{
    /** @var Practice */
    private $plugin;

    public function __construct(Practice $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        if (($member = $this->plugin->getPlayerManager()->getPlayer($player)) === null) $member = $this->plugin->getPlayerManager()->createPlayer($player);
        if ($member->getUsername() !== $player->getName()) $member->setUsername($player->getName());
        $this->plugin->getDuelAPI()->resetPlayer($player);
    }

    public function onChat(PlayerChatEvent $event): void
    {
        $member = $this->plugin->getPlayerManager()->getPlayer($event->getPlayer());
        if ($member->getRank() === "guest") {
            $event->setFormat(T::RESET . T::WHITE . "[" . $this->plugin->getPlayerManager()->getPlayer($event->getPlayer())->getRankedName() . "] " . $event->getPlayer()->getName() . ": " . $event->getMessage());
        } else if ($member->getRank() === "vip") {
            $event->setFormat(T::RESET . T::WHITE . "[" . T::GREEN . $this->plugin->getPlayerManager()->getPlayer($event->getPlayer())->getRankedName() . "+" . T::WHITE . "] " . $event->getPlayer()->getName() . ": " . $event->getMessage());
        } else if ($member->getRank() === "vip+") {
            $event->setFormat(T::RESET . T::WHITE . "[" . T::GREEN . T::BOLD . $this->plugin->getPlayerManager()->getPlayer($event->getPlayer())->getRankedName() . "++" . T::RESET . T::WHITE . "] " . $event->getPlayer()->getName() . ": " . $event->getMessage());
        } else if ($member->getRank() === "builder") {
            $event->setFormat(T::RESET . T::WHITE . "[" . T::BOLD . T::AQUA . "B" . T::RESET . T::WHITE . "] " . "[" . $this->plugin->getPlayerManager()->getPlayer($event->getPlayer())->getRankedName() . "] " . $event->getPlayer()->getName() . ": " . $event->getMessage());
        } else if ($member->getRank() === "helper") {
            $event->setFormat(T::RESET . T::WHITE . "[" . T::BOLD . T::YELLOW. "H" . T::RESET . T::WHITE . "] " . "[" . $this->plugin->getPlayerManager()->getPlayer($event->getPlayer())->getRankedName() . "] " . $event->getPlayer()->getName() . ": " . $event->getMessage());
        } else if ($member->getRank() === "mod") {
            $event->setFormat(T::RESET . T::WHITE . "[" . T::BOLD . T::LIGHT_PURPLE . "M" . T::RESET . T::WHITE . "] " . "[" . $this->plugin->getPlayerManager()->getPlayer($event->getPlayer())->getRankedName() . "] " . $event->getPlayer()->getName() . ": " . $event->getMessage());
        } else if ($member->getRank() === "admin") {
            $event->setFormat(T::RESET . T::WHITE . "[" . T::BOLD . T::RED. "A" . T::RESET . T::WHITE . "] " . "[" . $this->plugin->getPlayerManager()->getPlayer($event->getPlayer())->getRankedName() . "] " . $event->getPlayer()->getName() . ": " . $event->getMessage());
        } else if ($member->getRank() === "owner") {
            $event->setFormat(T::RESET . T::WHITE . "[" . T::BOLD . T::BLUE . "O" . T::RESET . T::WHITE . "] " . "[" . $this->plugin->getPlayerManager()->getPlayer($event->getPlayer())->getRankedName() . "] " . $event->getPlayer()->getName() . ": " . $event->getMessage());
        }
    }

    public function onItemUse(PlayerInteractEvent $event): void
    {
        if($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_AIR){
            $player = $event->getPlayer();
            $member = $this->plugin->getPlayerManager()->getPlayer($player);
            if ($event->getItem()->getId() === Item::ENDER_PEARL) {
                if ($player instanceof Player && $player->isOnline()) {
                    $member = $this->plugin->getPlayerManager()->getPlayer($player);
                    if ($member->getPearlCD() !== 0 || $player->isImmobile()) {
                        $event->setCancelled();
                    } else {
                        $member->startCD();
                    }
                }
            } else if ($event->getItem()->getId() === Item::SPLASH_POTION) {
                if ($player->isImmobile()) {
                    $event->setCancelled();
                } else if ($member->getDuel() === null && $member->getBotDuel() === null && $member->getPartyDuel() === null && $member->getFFA() === false) {
                    $event->setCancelled();
                }
            } else if ($event->getItem()->getNamedTag()->hasTag("duel")) {
                $form = new SimpleForm(function (Player $player, ?int $data): void {
                    if ($data !== null) {
                        if ($data === 0) {
                            $form = new SimpleForm(function (Player $player, ?int $data): void {
                                if ($data !== null) {
                                    if ($data === 0) {
                                        $member = $this->plugin->getPlayerManager()->getPlayer($player);
                                        if ($member->getDuel() !== null) {
                                            $duel = $member->getDuel();
                                            $this->plugin->getDuelManager()->deleteDuel($duel->getId());
                                            $member->setDuel(null);
                                        }
                                        $this->plugin->getDuelAPI()->findDuel($player, "nodebuff", false, "test");
                                        $player->sendMessage(T::RESET . T::GRAY . "You have queued for Unranked - NoDebuff!");
                                    }
                                }
                            });

                            $form->addButton(ucfirst("NoDebuff"));
                            $form->setTitle("Duels");
                            $form->setContent("Click to select a type!");

                            $player->sendForm($form);
                        } else if ($data === 1) {
                            $form = new SimpleForm(function (Player $player, ?int $data): void {
                                if ($data !== null) {
                                    if ($data === 0) {
                                        $member = $this->plugin->getPlayerManager()->getPlayer($player);
                                        if ($member->getDuel() !== null) {
                                            $duel = $member->getDuel();
                                            $this->plugin->getDuelManager()->deleteDuel($duel->getId());
                                            $member->setDuel(null);
                                        }
                                        $this->plugin->getDuelAPI()->findDuel($player, "nodebuff", true, "test");
                                        $player->sendMessage(T::RESET . T::GRAY . "You have queued for Ranked - NoDebuff!");
                                    }
                                }
                            });

                            $form->addButton(ucfirst("NoDebuff"));
                            $form->setTitle("Duels");
                            $form->setContent("Click to select a type!");

                            $player->sendForm($form);
                        }
                    }
                });

                $form->addButton(ucfirst("Unranked"));
                $form->addButton(ucfirst("Ranked"));
                $form->setTitle("Duels");
                $form->setContent("Click to select ranked/unranked!");

                $player->sendForm($form);
            } else if ($event->getItem()->getNamedTag()->hasTag("botduel")) {
                if ($member->getDuel() !== null) {
                    $player->sendMessage("You are queued for a duel!");
                    return;
                }

                $form = new SimpleForm(function (Player $player, ?int $data): void {
                    if ($data !== null) {
                        if ($data === 0) {
                            $this->plugin->getBotDuelManager()->createDuel($player, "easy");
                        }

                        if ($data === 1) {
                            $this->plugin->getBotDuelManager()->createDuel($player, "medium");
                        }

                        if ($data === 2) {
                            $this->plugin->getBotDuelManager()->createDuel($player, "hard");
                        }

                        if ($data === 3) {
                            $this->plugin->getBotDuelManager()->createDuel($player, "hacker");
                        }

                        if ($data === 4) {
                            $this->customBotForm($player);
                        }
                    }
                });

                $form->addButton(ucfirst("Easy"));
                $form->addButton(ucfirst("Medium"));
                $form->addButton(ucfirst("Hard"));
                $form->addButton(ucfirst("Hacker"));
                $form->addButton(ucfirst("Custom"));
                $form->setTitle("Bots");
                $form->setContent("Click to select a bot to fight!");

                $player->sendForm($form);
            } else if ($event->getItem()->getNamedTag()->hasTag("party")) {
                $member = $this->plugin->getPlayerManager()->getPlayer($player);
                if ($member->getParty() === null) {
                    $form = new SimpleForm(function (Player $player, ?int $data): void {
                        if ($data !== null) {
                            if ($data === 0) {
                                $party = $this->plugin->getPartyManager()->createParty($player);
                                $this->plugin->getPlayerManager()->getPlayer($player)->setParty($party);
                                $this->partyForm($player);
                            }

                            if ($data === 1) {
                                $this->invitesForm($player);
                            }
                        }
                    });

                    $form->addButton(ucfirst("Create"));
                    $form->addButton(ucfirst("Invites [" . count($member->getInvites())) . "]");
                    $form->setTitle("Partys");
                    $form->setContent("You are not in a party!");
                    $player->sendForm($form);
                }
            } else if ($event->getItem()->getNamedTag()->hasTag("ffa")) {
                    $form = new SimpleForm(function (Player $player, ?int $data): void {
                        if ($data !== null) {
                            if ($data === 0) {
                                $this->plugin->getFFA()->getRandomSpawn("nodebuff", $player);
                            }
                        }
                    });

                    $form->addButton(ucfirst("NoDebuff"));
                    $form->setTitle("FFA Warps");
                    $form->setContent("Click to select a FFA arena!");
                    $player->sendForm($form);
            }
        }
    }

    public function invitesForm(Player $player): void
    {
        $member = $this->plugin->getPlayerManager()->getPlayer($player);
        $invites = $member->getInvites();
        $form = new SimpleForm(function (Player $player, ?int $data) use ($member, $invites): void {
            if ($data !== null) {
                $i = 0;
                foreach ($invites as $invite) {
                    if ($data === $i) {
                        foreach ($this->plugin->getPartyManager()->getPartys() as $party) {
                            if ($party->getOwner() === array_search($invite, $invites)) {
                                $this->plugin->getPartyAPI()->acceptInvite($player, $party);
                                break;
                            }
                        }
                    }

                    $i++;
                }
            }
        });

        foreach ($invites as $invite) {
            $i = array_search($invite, $invites);
            $form->addButton(ucfirst($i));
        }

        $form->addButton(ucfirst("Exit"));

        $form->setTitle("Invites");
        $form->setContent("You have " . count($member->getInvites()) . " invites!");
        $player->sendForm($form);
    }

    public function partyForm(Player $player): void
    {

    }

    public function customBotForm(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, ?int $data): void {
            if ($data !== null) {
                if ($data === 0) {
                    $form = new CustomForm(function (Player $player, ?array $data = null): void {
                        if ($data !== null) {
                            if (isset($data[0]) && is_numeric($data[0]) && (float)$data[0] > 0) {
                                if ($data[0] > 10) {
                                    $player->sendMessage("Please enter a speed value of 10 or lower");
                                } else {
                                    $this->plugin->getPlayerManager()->getPlayer($player)->botSpeed = $data[0];
                                    $this->customBotForm($player);
                                }
                            } else {
                                $player->sendMessage("Please enter a numeric speed value");
                            }
                        }
                    });

                    $form->setTitle("Bot Speed");
                    $form->addInput("Speed", (string) $this->plugin->getPlayerManager()->getPlayer($player)->botSpeed);

                    $player->sendForm($form);
                }

                if ($data === 1) {
                    $form = new CustomForm(function (Player $player, ?array $data = null): void {
                        if ($data !== null) {
                            if (isset($data[0]) && is_numeric($data[0]) && (float)$data[0] > 0) {
                                if ($data[0] > 100) {
                                    $player->sendMessage("Please enter a speed value of 1000 or lower");
                                } else {
                                    $this->plugin->getPlayerManager()->getPlayer($player)->botHealth = $data[0];
                                    $this->customBotForm($player);
                                }
                            } else {
                                $player->sendMessage("Please enter a numeric health value");
                            }
                        }
                    });

                    $form->setTitle("Bot Health");
                    $form->addInput("Health", (string) $this->plugin->getPlayerManager()->getPlayer($player)->botHealth);

                    $player->sendForm($form);
                }

                if ($data === 2) {
                    $form = new CustomForm(function (Player $player, ?array $data = null): void {
                        if ($data !== null) {
                            if (isset($data[0]) && is_numeric($data[0]) && (float)$data[0] > 0) {
                                if ($data[0] > 100) {
                                    $player->sendMessage("Please enter a damage value of 100 or lower");
                                }  else {
                                    $this->plugin->getPlayerManager()->getPlayer($player)->botDamage = $data[0];
                                    $this->customBotForm($player);
                                }
                            } else {
                                $player->sendMessage("Please enter a numeric damage value");
                            }
                        }
                    });

                    $form->setTitle("Bot Damage");
                    $form->addInput("Damage", (string) $this->plugin->getPlayerManager()->getPlayer($player)->botDamage);

                    $player->sendForm($form);
                }

                if ($data === 3) {
                    $form = new CustomForm(function (Player $player, ?array $data = null): void {
                        if ($data !== null) {
                            if (isset($data[0]) && is_numeric($data[0]) && (float)$data[0] > 0) {
                                if ($data[0] > 100) {
                                    $player->sendMessage("Please enter a reach value of 100 or lower");
                                } else {
                                    $this->plugin->getPlayerManager()->getPlayer($player)->botReach = $data[0];
                                    $this->customBotForm($player);
                                }
                            } else {
                                $player->sendMessage("Please enter a numeric reach value");
                            }
                        }
                    });

                    $form->setTitle("Bot Reach");
                    $form->addInput("Reach", (string) $this->plugin->getPlayerManager()->getPlayer($player)->botReach);

                    $player->sendForm($form);
                }

                if ($data === 4) {
                    $form = new CustomForm(function (Player $player, ?array $data = null): void {
                        if ($data !== null) {
                            if (isset($data[0]) && is_numeric($data[0]) && (float)$data[0] > 0) {
                                if ($data[0] > 100) {
                                    $player->sendMessage("Please enter a accuracy value between 1 and 100");
                                } else {
                                    $this->plugin->getPlayerManager()->getPlayer($player)->botAccuracy = $data[0];
                                    $this->customBotForm($player);
                                }
                            } else {
                                $player->sendMessage("Please enter a numeric accuracy value");
                            }
                        }
                    });

                    $form->setTitle("Bot Accuracy");
                    $form->addInput("Accuracy", (string) $this->plugin->getPlayerManager()->getPlayer($player)->botAccuracy);

                    $player->sendForm($form);
                }

                if ($data === 5) {
                    $form = new CustomForm(function (Player $player, ?array $data = null): void {
                        if ($data !== null) {
                            if (isset($data[0]) && is_numeric($data[0]) && (float)$data[0] > 0) {
                                if ($data[0] > 100) {
                                    $player->sendMessage("Please enter an attack cooldown of 100 or lower");
                                }
                                $this->plugin->getPlayerManager()->getPlayer($player)->botAttackCooldown = $data[0];
                                $this->customBotForm($player);
                            } else {
                                $player->sendMessage("Please enter a numeric attack cooldown value");
                            }
                        }
                    });

                    $form->setTitle("Bot Attack Cooldown");
                    $form->addInput("Attack Cooldown", (string) $this->plugin->getPlayerManager()->getPlayer($player)->botAttackCooldown);

                    $player->sendForm($form);
                }

                if ($data === 6) {
                    $form = new CustomForm(function (Player $player, ?array $data = null): void {
                        if ($data !== null) {
                            if (isset($data[0]) && is_numeric($data[0]) && (float)$data[0] > 0) {
                                if ($data[0] > 10) {
                                    $player->sendMessage("Please enter a low reach value of 10 or lower");
                                } else {
                                    $this->plugin->getPlayerManager()->getPlayer($player)->botLowReach = $data[0];
                                    $this->customBotForm($player);
                                }
                            } else {
                                $player->sendMessage("Please enter a numeric low reach value");
                            }
                        }
                    });

                    $form->setTitle("Bot Low Reach");
                    $form->addInput("Low Reach", (string) $this->plugin->getPlayerManager()->getPlayer($player)->botLowReach);

                    $player->sendForm($form);
                }

                if ($data === 7) {
                    $form = new CustomForm(function (Player $player, ?array $data = null): void {
                        if ($data !== null) {
                            if (isset($data[0]) && is_numeric($data[0]) && (float)$data[0] > 0) {
                                if ($data[0] > 1) {
                                    $player->sendMessage("Please enter a safe distance value of 10 or lower");
                                } else {
                                    $this->plugin->getPlayerManager()->getPlayer($player)->botSafeDistance = $data[0];
                                    $this->customBotForm($player);
                                }
                            } else {
                                $player->sendMessage("Please enter a numeric safe distance value");
                            }
                        }
                    });

                    $form->setTitle("Bot Safe Distance");
                    $form->addInput("Safe Distance", (string) $this->plugin->getPlayerManager()->getPlayer($player)->botSafeDistance);

                    $player->sendForm($form);
                }

                if ($data === 8) {
                    $form = new CustomForm(function (Player $player, ?array $data = null): void {
                        if ($data !== null) {
                            if (isset($data[0]) && is_numeric($data[0]) && (float)$data[0] > 0) {
                                if ($data[0] > 100) {
                                    $player->sendMessage("Please enter a pot cooldown value of 100 or lower");
                                } else {
                                    $this->plugin->getPlayerManager()->getPlayer($player)->botPotTicks = $data[0] * 20;
                                    $this->customBotForm($player);
                                }
                            } else {
                                $player->sendMessage("Please enter a numeric pot cooldown value");
                            }
                        }
                    });

                    $form->setTitle("Bot Pot Cooldown");
                    $form->addInput("Pot Cooldown", (string) ($this->plugin->getPlayerManager()->getPlayer($player)->botPotTicks / 20));

                    $player->sendForm($form);
                }

                if ($data === 9) {
                    $form = new CustomForm(function (Player $player, ?array $data = null): void {
                        if ($data !== null) {
                            if (isset($data[0]) && is_numeric($data[0]) && (float)$data[0] > 0) {
                                if ($data[0] > 1000) {
                                    $player->sendMessage("Please enter a pot cooldown value of 1000 or lower");
                                } else {
                                    $this->plugin->getPlayerManager()->getPlayer($player)->botPotChance = $data[0];
                                    $this->customBotForm($player);
                                }
                            } else {
                                $player->sendMessage("Please enter a numeric pot chance value");
                            }
                        }
                    });

                    $form->setTitle("Bot Pot Chance");
                    $form->addInput("Pot Chance", (string) $this->plugin->getPlayerManager()->getPlayer($player)->botPotChance);

                    $player->sendForm($form);
                }

                if ($data === 10) {
                    $this->plugin->getBotDuelManager()->createDuel($player, "custom");
                }

            }
        });

        $form->addButton(ucfirst("Speed"));
        $form->addButton(ucfirst("Health"));
        $form->addButton(ucfirst("Damage"));
        $form->addButton(ucfirst("Reach"));
        $form->addButton(ucfirst("Accuracy"));
        $form->addButton(ucfirst("Attack Cooldown"));
        $form->addButton(ucfirst("Low Reach"));
        $form->addButton(ucfirst("Safe Distance"));
        $form->addButton(ucfirst("Pot Cooldown"));
        $form->addButton(ucfirst("Pot Chance"));
        $form->addButton(ucfirst("Go"));
        $form->setTitle("Custom Bot");
        $form->setContent("Click go to start the duel!");

        $player->sendForm($form);
    }

    public function onHunger(PlayerExhaustEvent $event): void
    {
        $event->setCancelled();
    }

    public function onDamage(EntityDamageEvent $event): void
    {
        $player = $event->getEntity();
        if ($player instanceof Human) {
            if ($player instanceof Player) {
                $pracPlayer = $this->plugin->getPlayerManager()->getPlayer($player);
                if (!$pracPlayer->canDamage()) {
                    $event->setCancelled();
                    return;
                }

                if ($event->getFinalDamage() >= $player->getHealth() + $player->getAbsorption()) {
                    $event->setCancelled();
                    $cause = $player->getLastDamageCause();
                    if ($pracPlayer->getDuel() !== null) {
                        $duel = $pracPlayer->getDuel();
                        $this->plugin->getDuelAPI()->eliminatePlayer($duel, $player);
                    }

                    if ($pracPlayer->getBotDuel() !== null) {
                        $this->plugin->getBotDuelAPI()->onPlayerKill($pracPlayer->getBotDuel());
                        return;
                    }

                    if ($pracPlayer->getPartyDuel() !== null) {
                        $this->plugin->getPartyAPI()->eliminatePlayer($pracPlayer->getPartyDuel(), $player);
                    }

                    if ($pracPlayer->getFFA() !== null) {
                        $this->plugin->getFFA()->onKill($player);
                    }

                    switch ($cause->getCause()) {
                        case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
                            $killer = $cause->getDamager();
                            if ($killer instanceof Player) {
                                if ($this->plugin->getPlayerManager()->getPlayer($killer)->canDamage() === false) {
                                    $event->setCancelled();
                                    return;
                                }

                                foreach ($this->plugin->getServer()->getOnlinePlayers() as $p) {
                                    $p->sendMessage(T::RESET . T::AQUA . $player->getName() . T::GRAY . " was killed by " . T::AQUA . $killer->getName());
                                }

                                $member = $this->plugin->getPlayerManager()->getPlayer($killer);
                                $member->addKS(1);
                                if ($member->getKS() >= 3) {
                                    $member->addCrowns(5 + 1 * $member->getKS());
                                } else {
                                    $member->addCrowns(5);
                                }
                            }

                            break;
                        case EntityDamageEvent::CAUSE_PROJECTILE:
                            $killer = $cause->getDamager();
                            if ($killer instanceof Player) {
                                if ($this->plugin->getPlayerManager()->getPlayer($killer)->canDamage() === false) {
                                    $event->setCancelled();
                                    return;
                                }
                                foreach ($this->plugin->getServer()->getOnlinePlayers() as $p) {
                                    $p->sendMessage(T::RESET . T::AQUA . $player->getName() . T::GRAY . " was shot by " . T::AQUA . $killer->getName());
                                }

                                $member = $this->plugin->getPlayerManager()->getPlayer($killer);
                                $member->addKS(1);
                                if ($member->getKS() >= 3) {
                                    $member->addCrowns(5 + 1 * $member->getKS());
                                } else {
                                    $member->addCrowns(5);
                                }
                            }

                            break;
                        case EntityDamageEvent::CAUSE_SUFFOCATION:
                            foreach ($this->plugin->getServer()->getOnlinePlayers() as $p) {
                                $p->sendMessage(T::RESET . T::AQUA . $player->getName() . T::GRAY . " could not breathe");
                            }
                            break;
                        case EntityDamageEvent::CAUSE_FALL:
                            foreach ($this->plugin->getServer()->getOnlinePlayers() as $p) {
                                $p->sendMessage(T::RESET . T::AQUA . $player->getName() . T::GRAY . " broke their legs");
                            }
                            break;
                        case EntityDamageEvent::CAUSE_FIRE:
                        case EntityDamageEvent::CAUSE_FIRE_TICK:
                            foreach ($this->plugin->getServer()->getOnlinePlayers() as $p) {
                                $p->sendMessage(T::RESET . T::AQUA . $player->getName() . T::GRAY . " went up in flames");
                            }
                            break;
                        case EntityDamageEvent::CAUSE_BLOCK_EXPLOSION:
                        case EntityDamageEvent::CAUSE_ENTITY_EXPLOSION:
                            foreach ($this->plugin->getServer()->getOnlinePlayers() as $p) {
                                $p->sendMessage(T::RESET . T::AQUA . $player->getName() . T::GRAY . " got blown up");
                            }
                            break;
                        case EntityDamageEvent::CAUSE_MAGIC:
                            foreach ($this->plugin->getServer()->getOnlinePlayers() as $p) {
                                $p->sendMessage(T::RESET . T::AQUA . $player->getName() . T::GRAY . " died from magic");
                            }
                            break;
                    }
                }
            } else {
                if ($player instanceof Bot) {
                    if ($event instanceof EntityDamageByEntityEvent) {
                        $event->setKnockBack(0);
                        $event->setAttackCooldown(10);
                    }

                    if ($event->getFinalDamage() >= $player->getHealth() + $player->getAbsorption()) {
                        $this->plugin->getBotDuelAPI()->onBotKill($player->getDuel());
                    }
                }
            }
        }
    }

    public function onBreak(BlockBreakEvent $event): void
    {
        $event->setCancelled();
    }

    public function onPlace(BlockPlaceEvent $event): void
    {
        $event->setCancelled();
    }

    public function onCraft(CraftItemEvent $event): void
    {
        $event->setCancelled();
    }

    public function onDrop(PlayerDropItemEvent $event): void
    {
        $event->setCancelled();
    }

    public function onSlotChange(InventoryTransactionEvent $event): void
    {
        $player = $event->getTransaction()->getSource();
        if ($player->isImmobile() || ($this->plugin->getPlayerManager()->getPlayer($player)->getDuel() === null && $this->plugin->getPlayerManager()->getPlayer($player)->getBotDuel() === null && $this->plugin->getPlayerManager()->getPlayer($player)->getPartyDuel() === null && $this->plugin->getPlayerManager()->getPlayer($player)->getFFA() === false)){
            $event->setCancelled();
        }
    }

    public function onInteract(PlayerInteractEvent $event): void
    {
        $b=$event->getBlock();
        if($b instanceof Anvil or $b instanceof Bed or $b instanceof BrewingStand or $b instanceof BurningFurnace or $b instanceof Button or $b instanceof Chest or $b instanceof CraftingTable or $b instanceof Door or $b instanceof EnchantingTable or $b instanceof EnderChest or $b instanceof FenceGate or $b instanceof Furnace or $b instanceof IronDoor or $b instanceof IronTrapDoor or $b instanceof Lever or $b instanceof TrapDoor or $b instanceof TrappedChest){
            $event->setCancelled();
        }
    }

    public function onDecay(LeavesDecayEvent $event): void
    {
        $event->setCancelled();
    }

    public function onLeave(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        $member = $this->plugin->getPlayerManager()->getPlayer($player);
        if ($member->getDuel() !== null) {
            $duel = $member->getDuel();
            if ($duel->getStatus() === true) {
                $this->plugin->getDuelManager()->deleteDuel($duel->getId());
                $member->setDuel(null);
            } else {
                $this->plugin->getDuelAPI()->eliminatePlayer($member->getDuel(), $player);
            }
        }

        if ($member->getBotDuel() !== null) {
            $duel = $member->getBotDuel();
            $this->plugin->getBotDuelAPI()->onPlayerKill($duel);
        }

        if ($member->getParty() !== null) {
            $this->plugin->getPartyAPI()->leaveParty($player, $member->getParty());
        }
    }

    public function onKick(PlayerKickEvent $event): void
    {
        $player = $event->getPlayer();
        $member = $this->plugin->getPlayerManager()->getPlayer($player);
        if ($member->getDuel() !== null) {
            $duel = $member->getDuel();
            if ($duel->getStatus() === true) {
                $this->plugin->getDuelManager()->deleteDuel($duel->getId());
                $member->setDuel(null);
            } else {
                $this->plugin->getDuelAPI()->eliminatePlayer($member->getDuel(), $player);
            }
        }

        if ($member->getBotDuel() !== null) {
            $duel = $member->getBotDuel();
            $this->plugin->getBotDuelAPI()->onPlayerKill($duel);
        }
    }

    public function onBurn(BlockBurnEvent $event): void
    {
        $event->setCancelled();
    }

    public function onExplosion(ExplosionPrimeEvent $event): void
    {
        $event->setBlockBreaking(false);
    }
}

