<?php

declare(strict_types=1);

namespace xSuper\Practice\Kits;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Human;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\Player;
use xSuper\Practice\Practice;
use pocketmine\utils\TextFormat as T;

class KitsAPI
{
    /** @var Practice */
    private $plugin;

    public function __construct(Practice $plugin)
    {
        $this->plugin = $plugin;
    }

    public function getKit(string $type,Human $player): void
    {
        $inv = $player->getInventory();
        switch ($type) {
            case "nodebuff":
                $helm = Item::get(Item::DIAMOND_HELMET)->setCustomName(T::RESET . T::RED . "NoDebuff");
                $helm->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 10));
                $chestplate = Item::get(Item::DIAMOND_CHESTPLATE)->setCustomName(T::RESET . T::RED . "NoDebuff");
                $chestplate->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 10));
                $leggings = Item::get(Item::DIAMOND_LEGGINGS)->setCustomName(T::RESET . T::RED . "NoDebuff");
                $leggings->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 10));
                $boots = Item::get(Item::DIAMOND_BOOTS)->setCustomName(T::RESET . T::RED . "NoDebuff");
                $boots->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 10));
                $player->getArmorInventory()->setHelmet($helm);
                $player->getArmorInventory()->setChestplate($chestplate);
                $player->getArmorInventory()->setLeggings($leggings);
                $player->getArmorInventory()->setBoots($boots);
                $sword = Item::get(Item::DIAMOND_SWORD)->setCustomName(T::RESET . T::RED . "NoDebuff");
                $sword->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 10));

                $inv->setContents([
                    0 => $sword,
                    1 => Item::get(Item::ENDER_PEARL, 0, 16)
                ]);

                $pot = Item::get(Item::SPLASH_POTION, 22, 34);
                $inv->addItem($pot);
                $player->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 99999, 1));
                break;
        }
    }
}

