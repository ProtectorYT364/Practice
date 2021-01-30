<?php

namespace xSuper\Practice\BotDuel;

use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\Player;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\utils\Color;
use pocketmine\block\{Block, Slab, Stair, Flowable};
use pocketmine\entity\Attribute;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\block\Liquid;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use xSuper\Practice\Libs\IEManager;
use xSuper\Practice\Practice;
use pocketmine\utils\TextFormat as T;

class Bot extends Human {

    /** @var string */
    public $name;

    /** @var string */
    public $type;

    /** @var ?Player */
    public $target = null;

    /** @var ?BotDuel */
    public $duel = null;

    /** @var bool */
    public $deactivated = false;

    /** @var int */
    public $potsUsed = 0;

    /** @var ?Position */
    public $randomPosition = null;

    /** @var int */
    public $newLocTicks = 60;

    /** @var float */
    public $gravity = 0.0072;

    /** @var float */
    public $potTicks;

    /** @var int */
    public $jumpTicks;

    /** @var int */
    public $attackcooldown;

    /** @var int */
    public $reachDistance;

    /** @var int */
    public $safeDistance;

    /** @var float */
    public $speed;

    /** @var float */
    public $lowReach;

    /** @var array */
    private $settings;

    /** @var Practice */
    private $plugin;

    public function __construct(Level $level, CompoundTag $nbt, array $settings, string $name, Practice $plugin)
    {
        if ($name === "Hacker") {
            $manager = new IEManager($plugin, "hacker.png");
        } else {
            $manager = new IEManager($plugin, "bot.png");
        }
        $this->setSkin($manager->skin);

        $this->settings = $settings;

        $this->speed = $settings["speed"];
        $this->safeDistance = $settings["safeDistance"];
        $this->lowReach = $settings["lowReach"];
        $this->potTicks = $settings["potTicks"];
        $this->attackcooldown = $settings["attackCooldown"];
        $this->reachDistance = $settings["reach"];
        $this->name = T::AQUA . T::BOLD . $name . " " . T::RESET . T::GRAY . "Bot";
        $this->type = $name;
        $health = $settings["health"];

        parent::__construct($level, $nbt);

        $this->setMaxHealth($health);
        $this->setHealth($health);
        $this->setNametag($this->name);

        $this->plugin = $plugin;

        $this->generateRandomPosition();
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setTarget($player): void
    {
        $target = $player;
        $this->target = ($target !== null ? $target->getName() : "");
    }

    public function hasTarget(): bool
    {
        if ($this->target === null || $this->getTarget() === null) return false;

        $player = $this->getTarget();

        return !$player->isSpectator();
    }

    public function getTarget(): ?Player
    {
        return $this->plugin->getServer()->getPlayerExact($this->target);
    }

    public function setDuel(?BotDuel $duel): void
    {
        $this->duel = $duel;
    }

    public function getDuel(): ?BotDuel
    {
        return $this->duel;
    }

    private function isDeactivated(): bool
    {
        return $this->deactivated;
    }

    public function setDeactivated(bool $result = true): void
    {
        $this->deactivated = $result;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNameTag(): string
    {
        return $this->name;
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        parent::entityBaseTick($tickDiff);

        if($this->isDeactivated()) return false;

        if (!$this->isAlive()) {
            if (!$this->closed) $this->flagForDespawn();
            return false;
        }

        $this->setNametag($this->getNameTag()." [".round($this->getHealth(), 1)."]");

        if ($this->hasTarget()) {
            if ($this->getLevel()->getName()==$this->getTarget()->getLevel()->getName()) {
                return $this->attackTarget();
            } else {
                $this->setDeactivated();
                if (!$this->closed) $this->flagForDespawn();
            }
        } else {
            $this->setDeactivated();
            if (!$this->closed) $this->flagForDespawn();
            return false;
        }
        if ($this->potTicks > 0) $this->potTicks--;
        if ($this->jumpTicks > 0) $this->jumpTicks--;
        if ($this->newLocTicks > 0) $this->newLocTicks--;
        if (!$this->isOnGround()) {
            if ($this->motion->y >- $this->gravity * 1) {
                $this->motion->y =- $this->gravity * 1;
            } else {
                $this->motion->y += $this->isUnderwater() ? $this->gravity :- $this->gravity;
            }
        } else {
            $this->motion->y -= $this->gravity;
        }

        if ($this->isAlive() && !$this->isClosed()) $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        if ($this->shouldPot()) $this->pot();
        if ($this->shouldJump()) $this->jump();
        if ($this->atRandomPosition() or $this->newLocTicks === 0){
            $this->generateRandomPosition();
            $this->newLocTicks = 60;
            return true;
        }

        $position = $this->getRandomPosition();
        $x= $position->x - $this->getX();
        $z= $position->z - $this->getZ();
        if ($x * $x + $z * $z < 4 + $this->getScale()) {
            $this->motion->x = 0;
            $this->motion->z = 0;
        } else {
            $this->motion->x = $this->getSpeed() * 0.15 * ($x / (abs($x) + abs($z)));
            $this->motion->z = $this->getSpeed() * 0.15 * ($z / (abs($x) + abs($z)));
        }

        $this->yaw = rad2deg(atan2(-$x, $z));
        $this->pitch = 0;
        if ($this->isAlive() && !$this->isClosed()) $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        if ($this->shouldPot()) $this->pot();
        if ($this->shouldJump()) $this->jump();
        if ($this->isAlive()) $this->updateMovement();
        return $this->isAlive();
    }
    public function attackTarget(): bool
    {
        if($this->isDeactivated()) return false;
        if(!$this->isAlive()){
            if(!$this->closed) $this->flagForDespawn();
            return false;
        }
        $target=$this->getTarget();
        if($target===null){
            $this->target=null;
            return true;
        }
        if($this->getLevel()->getName()!=$target->getLevel()->getName()){
            $this->setDeactivated();
            if(!$this->closed) $this->flagForDespawn();
        }
        $x=$target->x - $this->x;
        $y=$target->y - $this->y;
        $z=$target->z - $this->z;
        if($this->potTicks > 0) $this->potTicks--;
        if($this->jumpTicks > 0) $this->jumpTicks--;
        if(!$this->isOnGround()){
            $this->reachDistance = $this->lowReach;
            if($this->distance($target) <= 5){
                $this->motion->x=$this->getSpeed() * 0.15 * -$x;
                $this->motion->z=$this->getSpeed() * 0.15 * -$z;
            }
            if($this->motion->y > -$this->gravity * 1){ //default is 4
                $this->motion->y=-$this->gravity * 1;
            }else{
                $this->motion->y += $this->isUnderwater() ? $this->gravity:-$this->gravity;
            }
        }else{
            $this->reachDistance = $this->settings["reach"];
            $this->motion->y -= $this->gravity;
        }
        if($this->isAlive() and !$this->isClosed()) $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        if($this->shouldPot()) $this->pot();
        if($this->shouldJump()) $this->jump();
        if($this->distance($target) <= $this->safeDistance){
            $this->motion->x=0;
            $this->motion->z=0;
        }else{
            if($target->isSprinting()){
                $this->motion->x=$this->getSpeed() * 0.20 * ($x / (abs($x) + abs($z)));
                $this->motion->z=$this->getSpeed() * 0.20 * ($z / (abs($x) + abs($z)));
            }else{
                $this->motion->x=$this->getSpeed() * 0.15 * ($x / (abs($x) + abs($z)));
                $this->motion->z=$this->getSpeed() * 0.15 * ($z / (abs($x) + abs($z)));
            }
        }
        $this->yaw=rad2deg(atan2(-$x, $z));
        $this->pitch=rad2deg(-atan2($y, sqrt($x * $x + $z * $z)));
        if($this->shouldPot()) $this->pot();
        if($this->shouldJump()) $this->jump();
        if($this->isAlive() and !$this->isClosed()) $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        if(0>=$this->attackcooldown){
            if($this->distance($target) <= $this->reachDistance){
                $event=new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getBaseAttackDamage());
                if($this->isAlive()) $this->broadcastEntityEvent(4);
                if(mt_rand(0, 100) <= $this->settings["accuracy"]){
                    $target->attack($event);
                    //$target->sendMessage("Hit");
                    $volume=0x10000000 * (min(30, 10) / 5);
                    $target->getLevel()->broadcastLevelSoundEvent($target->asVector3(), LevelSoundEventPacket::SOUND_ATTACK, (int) $volume);
                }else{
                    //$target->sendMessage("Missed");
                    $volume=0x10000000 * (min(30, 10) / 5);
                    $target->getLevel()->broadcastLevelSoundEvent($this->asVector3(), LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE, (int) $volume);
                }
                $this->attackcooldown = $this->settings["attackCooldown"];
            }
        }
        if($this->isAlive()) $this->updateMovement();
        $this->attackcooldown--;
        return $this->isAlive();
    }
    public function attack(EntityDamageEvent $source):void{
        parent::attack($source);
        if($source->isCancelled()){
            $source->setCancelled();
            return;
        }
        if($source instanceof EntityDamageByEntityEvent){
            $killer=$source->getDamager();
            if($killer instanceof Player){
                if($killer->isSpectator()){
                    $source->setCancelled(true);
                    return;
                }
                $deltaX=$this->x - $killer->x;
                $deltaZ=$this->z - $killer->z;
                $this->knockBack($killer, 0, $deltaX, $deltaZ);
            }
        }
    }
    public function knockBack($attacker, float $damage, float $x, float $z, float $base=0.4):void
    {
        $xzKB = 0.389;
        $yKb = 0.6;
        $f = sqrt($x * $x + $z * $z);
        if ($f <= 0) {
            return;
        }
        if (mt_rand() / mt_getrandmax() > $this->getAttributeMap()->getAttribute(Attribute::KNOCKBACK_RESISTANCE)->getValue()) {
            $f = 1 / $f;
            $motion = clone $this->motion;
            $motion->x /= 1;
            $motion->y /= 1;
            $motion->z /= 1;
            $motion->x += $x * $f * $xzKB;
            $motion->y += $yKb;
            $motion->z += $z * $f * $xzKB;
            if ($motion->y > $yKb) {
                $motion->y = $yKb;
            }
            if ($this->isAlive() and !$this->isClosed()) $this->move($motion->x * 1, $motion->y * 1, $motion->z * 1);
        }
    }

    public function kill():void{
        parent::kill();
    }
    public function atRandomPosition(): bool
    {
        return $this->getRandomPosition()==null or $this->distance($this->getRandomPosition()) <= 2;
    }
    public function getRandomPosition(): ?Position
    {
        return $this->randomPosition;
    }
    public function generateRandomPosition(){
        $minX=$this->getFloorX() - 8;
        $minY=$this->getFloorY() - 8;
        $minZ=$this->getFloorZ() - 8;
        $maxX=$minX + 16;
        $maxY=$minY + 16;
        $maxZ=$minZ + 16;
        $level=$this->getLevel();
        for($attempts=0; $attempts < 16; ++$attempts){
            $x=mt_rand($minX, $maxX);
            $y=mt_rand($minY, $maxY);
            $z=mt_rand($minZ, $maxZ);
            while($y >= 0 and !$level->getBlockAt($x, $y, $z)->isSolid()){
                $y--;
            }
            if($y < 0){
                continue;
            }
            $blockUp=$level->getBlockAt($x, $y + 1, $z);
            $blockUp2=$level->getBlockAt($x, $y + 2, $z);
            if($blockUp->isSolid() or $blockUp instanceof Liquid or $blockUp2->isSolid() or $blockUp2 instanceof Liquid){
                continue;
            }
            break;
        }
        $this->randomPosition=new Vector3($x, $y + 1, $z);
    }
    public function getSpeed(): float
    {
        return ($this->isUnderwater() ? $this->speed / 2:$this->speed);
    }
    public function getBaseAttackDamage(): int
    {
        return $this->settings["damage"];
    }
    public function getFrontBlock($y=0): Block
    {
        $dv=$this->getDirectionVector();
        $pos=$this->asVector3()->add($dv->x * $this->getScale(), $y + 1, $dv->z * $this->getScale())->round();
        return $this->getLevel()->getBlock($pos);
    }
    public function shouldJump(): bool
    {
        if($this->jumpTicks > 0) return false;
        if(!$this->isOnGround()) return false;
        return $this->isCollidedHorizontally or
            ($this->getFrontBlock()->getId()!=0 or $this->getFrontBlock(-1) instanceof Stair) or
            ($this->getLevel()->getBlock($this->asVector3()->add(0,-0,5)) instanceof Slab and
                (!$this->getFrontBlock(-0.5) instanceof Slab and $this->getFrontBlock(-0.5)->getId()!=0)) and
            $this->getFrontBlock(1)->getId()==0 and
            $this->getFrontBlock(2)->getId()==0 and
            !$this->getFrontBlock() instanceof Flowable and
            $this->jumpTicks==0;
    }
    public function shouldPot(): bool
    {
        if($this->potsUsed >= 25) return false;
        if($this->potTicks > 0) return false;
        return mt_rand(7, 9) >= $this->getHealth();
    }
    public function getJumpMultiplier(): int
    {
        return 64;
    }

    public function instantPot($item, $player, bool $animate=false){
        if($item===Item::SPLASH_POTION){
            $player->setHealth($player->getHealth() + 8);

            $colors=[new Color(0xf8, 0x24, 0x23)];
            $player->getLevel()->broadcastLevelEvent($player->asVector3()->add($player->getDirectionVector()->x + 0.3, 1, 0), LevelEventPacket::EVENT_PARTICLE_SPLASH, Color::mix(...$colors)->toARGB());
            $player->getLevel()->broadcastLevelSoundEvent($player->asVector3(), LevelSoundEventPacket::SOUND_GLASS);
        }
        if($animate===true){
            $packet=new AnimatePacket();
            $packet->action=AnimatePacket::ACTION_SWING_ARM;
            $packet->entityRuntimeId=$player->getId();
            $this->plugin->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $packet);
        }
    }

    public function jump():void{
        if($this->jumpTicks > 0) return;
        $this->motion->y=$this->gravity * $this->getJumpMultiplier();
        if($this->isAlive() and !$this->isClosed()) $this->move($this->motion->x * 1.15, $this->motion->y, $this->motion->z * 1.15);
        $this->jumpTicks=10; //($this->getJumpMultiplier()==4 ? 2:5);
    }
    public function pot():void
    {
        if($this->potsUsed >= 25) return;
        if(mt_rand(0, 1000) > $this->settings["potChance"]){
            $this->instantPot(Item::SPLASH_POTION, $this, true);
            $this->potTicks = $this->settings["potTicks"];
            $this->potsUsed++;
        }
    }
}
