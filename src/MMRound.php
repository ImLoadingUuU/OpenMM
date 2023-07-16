<?php

namespace MeTooIDK\OpenMM;
use Exception;

use pocketmine\utils\TextFormat;
use  MeTooIDK\OpenMM\ScoreBoardAPI;

class MMRound
{
    private $scoreboard;

    public function __construct(private Main $plugin)
    {
    }
    public function startRound(int $worldId)
    {
        $this->plugin->getLogger()->info("Starting Round");
        if(!isset($this->plugin->maps_data[$worldId])) {
           throw new Exception("Map Not Found");
        }
        $world = $this->plugin->getServer()->getWorldManager()->getWorld($worldId);
        $players = $world->getPlayers();
        if (!$players) {
            throw new Exception("No Players Found");
        }
        // Create Scoreboard for all player
        foreach ($players as $player) {
            ScoreBoardAPI::sendScore($player,TextFormat::BOLD . TextFormat::YELLOW . "OPENMM Beta");
            ScoreBoardAPI::setScoreLine($player,1,"");
            ScoreBoardAPI::setScoreLine($player,2,TextFormat::WHITE . "Role: " . TextFormat::GREEN . " undefined");
            ScoreBoardAPI::setScoreLine($player,3,"");
            ScoreBoardAPI::setScoreLine($player,4,TextFormat::WHITE . "Innocents Left: " . TextFormat::GREEN . " undefined");
            ScoreBoardAPI::setScoreLine($player,5,"");
            ScoreBoardAPI::setScoreLine($player,6,TextFormat::WHITE . "Detective: " . TextFormat::GREEN . " undefined");
            ScoreBoardAPI::setScoreLine($player,7,"");
            ScoreBoardAPI::setScoreLine($player,8,TextFormat::WHITE . "Score: " . TextFormat::GREEN . " undefined");
            ScoreBoardAPI::setScoreLine($player,9,"");
            ScoreBoardAPI::setScoreLine($player,8,TextFormat::YELLOW . "OpenMM v0.0.1");
        }
    }
}
