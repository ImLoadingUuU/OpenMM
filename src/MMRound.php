<?php

namespace MeTooIDK\OpenMM;

use Exception;

use pocketmine\utils\TextFormat;
use  MeTooIDK\OpenMM\ScoreBoardAPI;

class MMRound
{
    private $scoreboard;

    private $timeLeft;
    
    private $murder;

    private $detective;
    
    private $world;
    public function __construct(private Main $plugin, private int $worldId)
    {
        $this->world = $this->plugin->getServer()->getWorldManager()->getWorld($this->worldId);
    }
    public function updateSbData(int $time, int $inncLefts, bool $detectiveAlive)
    {
        $players = $this->world->getPlayers();
        foreach ($players as $player) {
            ScoreBoardAPI::editLineScore($player, 4, "", "$inncLefts");
            ScoreBoardAPI::editLineScore($player, 8, "", $detectiveAlive ? "Yes" : "No");
            ScoreBoardAPI::editLineScore($player, 5, "", "$time");
        }
    }
    public function startRound()
    {
        $this->plugin->getLogger()->info("Starting Round");
        if (!isset($this->plugin->maps_data[$this->worldId])) {
            throw new Exception("Map Not Found");
        }
        $world = $this->plugin->getServer()->getWorldManager()->getWorld($this->worldId);
        $players = $world->getPlayers();
        if (!$players) {
            throw new Exception("No Players Found");
        }
        // Create Scoreboard for all player
        foreach ($players as $player) {
            ScoreBoardAPI::sendScore($player, TextFormat::BOLD . TextFormat::YELLOW . "MURDER");
            ScoreBoardAPI::setScoreLine($player, 2, TextFormat::WHITE . "Role: " . TextFormat::GREEN . " undefined");
            ScoreBoardAPI::setScoreLine($player, 4, TextFormat::WHITE . "Innocents Left: " . TextFormat::GREEN . " undefined");
            ScoreBoardAPI::setScoreLine($player, 5, TextFormat::WHITE . "Time Left" . TextFormat::GREEN . " undefined");
            ScoreBoardAPI::setScoreLine($player, 8, TextFormat::WHITE . "Detective: " . TextFormat::GREEN . " undefined");
            ScoreBoardAPI::setScoreLine($player, 9, TextFormat::WHITE . "Score: " . TextFormat::GREEN . " undefined");
            ScoreBoardAPI::setScoreLine($player, 10, TextFormat::YELLOW . "OpenMM v0.0.1");
           
        }
        $murderPerSec = function(){
          
        };
        $this->plugin->getScheduler()->scheduleRepeatingTask(new OpenMMTask($murderPerSec), 20);
    }
}
