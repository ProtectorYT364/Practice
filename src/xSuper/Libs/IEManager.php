<?php

declare(strict_types = 1);

 #   /\ /\___ _ __ __ _ ___
 #   \ \ / / _ \ '__/ _`|/_\
 #    \ V / __/ | | (_| |__/
 #     \_/ \___ |_| \__,|\___
 #                  |___/ 
 
namespace xSuper\Practice\Libs;


use pocketmine\entity\Skin;
use xSuper\Practice\Practice;

class IEManager {


    /** @var Skin */
    public $skin;

    /** @var string */
    public $name;

    /** @var Practice */
    private $plugin;

    /**
     * Manager constructor.
     *
     * @param Practice $plugin
     * @param string $path
     */
    public function __construct(Practice $plugin, string $path) {
        $this->plugin = $plugin;
        $this->path = $path;
        $this->init();
    }

    public function init(): void {
       
        $path = $this->plugin->getDataFolder() . $this->path;
        $this->skin = SkinConverter::createSkin(SkinConverter::getSkinDataFromPNG($path));
        
    }
}
