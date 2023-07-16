<?php

namespace MeTooIDK\OpenMM;

use Exception;

use pocketmine\utils\TextFormat;
use  MeTooIDK\OpenMM\ScoreBoardAPI;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\scheduler\TaskHandler;

class MMRound
{
    private $scoreboard;
    public $timeLeft;
    public Player $murder;
    public Player $detective;
    private $world;

    private TaskHandler $currentTask;
    public function __construct(private Main $plugin, private int $worldId, private int $roundTime = 240)
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
    private function pickRandom(array $players)
    {
        $this->murder  = $players[random_int(1, count($players))];
        $this->detective  = $players[random_int(1, count($players))];
        if ($this->murder == $this->detective) {
            $this->plugin->getLogger()->info(TextFormat::YELLOW . " OpenMM - Murder and Detective are Same. Reroll");
            $this->pickRandom($players);
        }
    }
    private function playSonud(Player $player, string $soundName)
    {
        $pk = new PlaySoundPacket();
        $pk->soundName = $soundName; // string
        $pk->volume = 1; // numeric
        $pk->pitch = 1; // numeric
        $pk->x = $player->getPosition()->getX();
        $pk->y = $player->getPosition()->getY();
        $pk->z = $player->getPosition()->getZ();
        $player->getNetworkSession()->sendDataPacket($pk);
    }
    public function endRound() {
        // Kill Current Task
        $this->currentTask->cancel();
        
    }
    public function startRound()
    {
        $this->plugin->getLogger()->info("Starting Round");
        if (!isset($this->plugin->maps_data[$this->worldId])) {
            throw new Exception("Map Not Found");
        }
        $world = $this->plugin->getServer()->getWorldManager()->getWorld($this->worldId);
        $mapCfg = $this->plugin->maps_data[$this->worldId];
        if (!$mapCfg["spawnPoints"]) {
            throw new Exception("No Spawn Points Found");
        }
        $players = $world->getPlayers();
        if (!$players) {
            throw new Exception("No Players Found");
        }
        // Pick Random Murder and Detective
        $this->pickRandom($players);

        // Create Scoreboard for all player
        foreach ($players as $player) {
            $rp = $mapCfg["spawnPoints"][random_int(1,count($mapCfg["spawnPoints"]))];
            ScoreBoardAPI::sendScore($player, TextFormat::BOLD . TextFormat::YELLOW . "MURDER");
            ScoreBoardAPI::setScoreLine($player, 2, TextFormat::WHITE . "Role: " . TextFormat::GREEN . " undefined");
            ScoreBoardAPI::setScoreLine($player, 4, TextFormat::WHITE . "Innocents Left: " . TextFormat::GREEN . " undefined");
            ScoreBoardAPI::setScoreLine($player, 5, TextFormat::WHITE . "Time Left" . TextFormat::GREEN . " undefined");
            ScoreBoardAPI::setScoreLine($player, 8, TextFormat::WHITE . "Detective: " . TextFormat::GREEN . " undefined");
            ScoreBoardAPI::setScoreLine($player, 9, TextFormat::WHITE . "Score: " . TextFormat::GREEN . " undefined");
            ScoreBoardAPI::setScoreLine($player, 10, TextFormat::YELLOW . "OpenMM v0.0.1");
            if ($player->getId() == $this->murder->getId()) {
                $this->playSonud($player, "random.explode");
                $player->sendTitle(TextFormat::RED . "Murder", "Your goal is kill everyone");
            } else if ($player->getId()  == $this->detective->getId()) {
                $this->playSonud($player, "random.levelup");
                $player->sendTitle(TextFormat::AQUA . "Detective", "Your goal is kill murder");
            } else {
                $this->playSonud($player, "random.explode");
                $player->sendTitle(TextFormat::GREEN . "Innocents", "Survive.");
            }
            $player->teleport(new Vector3($rp["x"],$rp["y"],$rp["z"]));
        }
        $murderPerSec = function () {
        };
        $this->currentTask = $this->plugin->getScheduler()->scheduleRepeatingTask(new OpenMMTask($murderPerSec), 20);
        $this->plugin->getServer()->getPluginManager()->registerEvents(new MMRoundOnDie($this), $this->plugin);
    }
}
