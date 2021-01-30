<?php
declare(strict_types = 1);

namespace xSuper\Practice\Tasks;

use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat as T;
use xSuper\Practice\Libs\ScoreAPI;
use xSuper\Practice\Practice;

class UpdateScoreboardsTask extends Task{

    /** @var Practice */
    private $plugin;

    /** @var array */
    private $titles = [T::RESET . T::BOLD . T::AQUA . "V" . T::RESET . T::GRAY . "oltage", T::RESET . T::GRAY . "V" . T::BOLD . T::AQUA . "o" . T::RESET . T::GRAY . "ltage", T::RESET . T::GRAY . "Vo" . T::BOLD . T::AQUA . "l" . T::RESET . T::GRAY . "tage", T::RESET . T::GRAY . "Vol" . T::BOLD . T::AQUA . "t" . T::RESET . T::GRAY . "age", T::RESET . T::GRAY . "Volt" . T::BOLD . T::AQUA . "a" . T::RESET . T::GRAY . "ge", T::RESET . T::GRAY . "Volta" . T::BOLD . T::AQUA . "g" . T::RESET . T::GRAY . "e", T::RESET . T::GRAY . "Voltag" . T::BOLD . T::AQUA . "e"];

    public function __construct(Practice $plugin){
        $this->plugin = $plugin;
    }

    public function onRun(int $tick){
        $this->plugin->i = $this->plugin->i + 1;

        if ($this->plugin->i === 7) {
            $this->plugin->i = 0;
        }

        $str = $this->titles[$this->plugin->i];

        ScoreAPI::setScore(Practice::$objectiveName, $str . T::RESET . T::DARK_GRAY . " - Practice");


        foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
            ScoreAPI::sendScore(Practice::$objectiveName, $player);
            $member = $this->plugin->getPlayerManager()->getPlayer($player);
            if ($member === null) return;
            if ($member->getDuel() !== null && $member->getDuel()->getStatus() === false) {
                $duel = $member->getDuel();
                ScoreAPI::setScoreLine(Practice::$objectiveName, $player, 1, T::RESET . "  ");
                if ($duel->getRanked() === true) ScoreAPI::setScoreLine(Practice::$objectiveName, $player, 2, T::AQUA . T::BOLD . " Game: " . T::RESET . T::DARK_GRAY . " Ranked");
                else ScoreAPI::setScoreLine(Practice::$objectiveName, $player, 2, T::AQUA . T::BOLD . " Game: " . T::RESET . T::DARK_GRAY . "Unranked");
                ScoreAPI::setScoreLine(Practice::$objectiveName, $player, 3, T::GRAY . "  Type: " . T::AQUA . $duel->getType());
                ScoreAPI::setScoreLine(Practice::$objectiveName, $player, 4, T::GRAY . "  Time: " . T::AQUA . $duel->getTime());
                ScoreAPI::setScoreLine(Practice::$objectiveName, $player, 5, T::RESET . " ");
                ScoreAPI::setScoreLine(Practice::$objectiveName, $player, 6, T::AQUA . T::BOLD . " Players:");
                foreach ($duel->getPlayers() as $duelPlayer){
                    if ($duelPlayer === $player->getName()){
                        ScoreAPI::setScoreLine(Practice::$objectiveName, $player, 7, T::GRAY . "  Your Ping: " . T::AQUA .  $player->getPing());
                    } else {
                        ScoreAPI::setScoreLine(Practice::$objectiveName, $player, 7, T::GRAY . "  " . $duelPlayer . "'s Ping: " . T::AQUA . $this->plugin->getServer()->getPlayer($duelPlayer)->getPing());
                    }
                }
            } else {
                ScoreAPI::setScoreLine(Practice::$objectiveName, $player, 1, T::RESET . "  ");
                ScoreAPI::setScoreLine(Practice::$objectiveName, $player, 2, T::RESET . T::BOLD . T::AQUA . "Server:");
                ScoreAPI::setScoreLine(Practice::$objectiveName, $player, 3, T::RESET . T::GRAY . "  Online: " . T::AQUA . count($this->plugin->getServer()->getOnlinePlayers()));
                ScoreAPI::setScoreLine(Practice::$objectiveName, $player, 4, T::RESET  . T::GRAY . "  TPS: " . T::AQUA . $this->plugin->getServer()->getTicksPerSecond());
                ScoreAPI::setScoreLine(Practice::$objectiveName, $player, 6, T::RESET . T::BOLD . T::AQUA . $player->getName() . ":");
                ScoreAPI::setScoreLine(Practice::$objectiveName, $player, 7, T::RESET . T::GRAY . "  Crowns: " . T::AQUA . $member->getCrowns());
                ScoreAPI::setScoreLine(Practice::$objectiveName, $player, 8, T::RESET . T::GRAY . "  Kill Streak: " . T::AQUA . $member->getKS());
            }
        }
    }
}
