<?php

namespace MeTooIDK\OpenMM;

use Exception;

use pocketmine\utils\TextFormat;
use  MeTooIDK\OpenMM\ScoreBoardAPI;
use pocketmine\event\HandlerList;
use pocketmine\event\HandlerListManager;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\TaskHandler;
use pocketmine\updater\UpdateInfo;

class MMRound
{
    private $scoreboard;
    private $currentListener;
    public $timeLeft;
    public Player $murder;
    public Player $detective;

    public bool $detectiveAlive = true;
    public bool $murderAlive = true;
    public $world;

    public $players;

    private TaskHandler $currentTask;
    public function __construct(public Main $plugin, private int $worldId, private int $roundTime = 240)
    {
        $this->world = $this->plugin->getServer()->getWorldManager()->getWorld($this->worldId);
    }
    public function updateSbData(int $time, int $inncLefts)
    {

        foreach ($this->players as $player) {
            ScoreBoardAPI::editLineScore($player, 2, "", "$inncLefts");
            ScoreBoardAPI::editLineScore($player, 4, $this->detectiveAlive ? "Yes" : "No", $this->detectiveAlive ? "Yes" : "No");
            ScoreBoardAPI::editLineScore($player, 3, $time + 1, "$time");
        }
    }
    private function pickRandom(array $players)
    {
        $this->murder  = $players[array_rand($players)];
        $this->detective  = $players[array_rand($players)];
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
    public function endRound()
    {
        // Kill Current Task
        $this->currentTask->cancel();
        $currentWorld = $this->plugin->getServer()->getWorldManager()->getWorld($this->worldId);
        $ps = $currentWorld->getPlayers();
        foreach ($ps as $p) {
            $this->playSonud($p, "random.levelup");
            $this->playSonud($p, "firework.blast");
            ScoreBoardAPI::removeScore($p);
            if ($this->timeLeft <= 0) {
                $p->sendTitle(TextFormat::GREEN . "Innocent Survived", "Congrats!");
            } else if ($this->murderAlive) {
                $p->sendTitle(TextFormat::RED . "Murder Won", "Murder Killed Everyone!");
            } else if (!$this->murderAlive and $this->detectiveAlive) {
                $p->sendTitle(TextFormat::AQUA . "Detective Won", "The Detective killed murder");
            } else {
                $p->sendTitle(TextFormat::GREEN . "Innocent Won", "The Innocent has killed the murder");
            }
        }
        // Remove Listener
     
    
    }
    public function setPlayerAsDead(Player $pickedPlayer){
        
    }
    public function startRound()
    {
        $this->timeLeft = $this->roundTime;
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
        $this->players = $players;
        // Pick Random Murder and Detective
        $this->pickRandom($this->players);

        // Create Scoreboard for all player
        foreach ($this->players as $player) {
            $player->setGamemode(GameMode::ADVENTURE());
            $rp = $mapCfg["spawnPoints"][array_rand($mapCfg["spawnPoints"])];
            ScoreBoardAPI::sendScore($player, TextFormat::BOLD . TextFormat::YELLOW . "MURDER");
            ScoreBoardAPI::setScoreLine($player, 1, TextFormat::WHITE . "Role: " . TextFormat::GREEN . " undefined");
            ScoreBoardAPI::setScoreLine($player, 2, TextFormat::WHITE . "Innocents Left: " . TextFormat::GREEN . " idk");
            ScoreBoardAPI::setScoreLine($player, 3, TextFormat::WHITE . "Time Left: " . TextFormat::GREEN . " " . $this->timeLeft . "s");
            ScoreBoardAPI::setScoreLine($player, 4, TextFormat::WHITE . "Detective: " . TextFormat::GREEN . " Yes");
            ScoreBoardAPI::setScoreLine($player, 5, TextFormat::WHITE . "Score: " . TextFormat::GREEN . " undefined");
            ScoreBoardAPI::setScoreLine($player, 6, TextFormat::YELLOW . "OpenMM v0.0.1");
            if ($player->getId() == $this->murder->getId()) {
                $this->playSonud($player, "random.explode");
                $player->sendTitle(TextFormat::RED . "Murder", "Your goal is kill everyone");
               
                ScoreboardAPI::editLineScore($player, 1, "undefined", TextFormat::RED . " Murder");
            } else if ($player->getId()  == $this->detective->getId()) {
                $this->playSonud($player, "random.levelup");
                $player->sendTitle(TextFormat::AQUA . "Detective", "Your goal is kill murder");
                $bow = VanillaItems::BOW();
                $bow->setLore([TextFormat::AQUA . " Detective Bow Pro Max"]);

                $player->getInventory()->addItem($bow);
                $player->getInventory()->addItem(VanillaItems::ARROW());
                ScoreboardAPI::editLineScore($player, 1, "undefined", TextFormat::AQUA . " Detective");
            } else {
                $this->playSonud($player, "random.explode");
                $player->sendTitle(TextFormat::GREEN . "Innocents", "Survive.");
                ScoreboardAPI::editLineScore($player, 1, "undefined", TextFormat::GREEN . " Innocents");
            }
            $player->teleport(new Vector3($rp["x"], $rp["y"], $rp["z"]));
        }
        $murderPerSec = function () {
            $this->plugin->getLogger()->info("Time Changed: {$this->timeLeft}");
            $this->timeLeft = $this->timeLeft - 1;
            $this->updateSbData($this->timeLeft, 114514);
            if ($this->timeLeft < 0) {
                $this->endRound();
            }
        };
        $this->currentTask = $this->plugin->getScheduler()->scheduleRepeatingTask(new OpenMMTask($murderPerSec), 20);
        
       $this->currentListener = new MMRoundEvents($this);
       $this->plugin->getServer()->getPluginManager()->registerEvents($this->currentListener, $this->plugin);
       
    }
}
