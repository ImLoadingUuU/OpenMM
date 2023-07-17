<?php

namespace MeTooIDK\OpenMM;

use Exception;


use pocketmine\utils\TextFormat;
use  MeTooIDK\OpenMM\ScoreBoardAPI;
use pocketmine\event\HandlerList;
use pocketmine\event\HandlerListManager;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
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

    public $aliveLefts;
    private TaskHandler $currentTask;
    public function __construct(public Main $plugin, private int $worldId, private int $roundTime = 240)
    {
        $this->world = $this->plugin->getServer()->getWorldManager()->getWorld($this->worldId);
    }
    public function updateSbData(int $time)
    {

        foreach ($this->players as $player) {
            ScoreBoardAPI::editLineScore($player, 2, $this->aliveLefts + 1, $this->aliveLefts);
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
    public function endRound(string $whoWin)
    {
        // Kill Current Task
        $this->currentTask->cancel();
        $currentWorld = $this->plugin->getServer()->getWorldManager()->getWorld($this->worldId);
        $ps = $currentWorld->getPlayers();
        foreach ($ps as $p) {
            $this->playSonud($p, "random.levelup");
            $this->playSonud($p, "firework.blast");
            ScoreBoardAPI::removeScore($p);
            switch ($whoWin) {
                case "innocents":
                    $p->sendTitle(TextFormat::GREEN . "Innocent Survived", "Congrats!");
                case "murder":
                    $p->sendTitle(TextFormat::RED . "Murder Won", "Murder Killed Everyone!");
                case "detective":
                    $p->sendTitle(TextFormat::AQUA . "Detective Won", "The Detective killed murder");
                default:
                   $p->sendTitle(TextFormat::GRAY . "Who wins?" , " I Don't know.");
            }
            $p->sendMessage(TextFormat::BOLD . " ROUND INFO");
            $p->sendMessage(TextFormat::BOLD . " Round Duration: " . $this->roundTime - $this->timeLeft);
            $p->sendMessage(TextFormat::BOLD . " ===================");
            $p->sendMessage(TextFormat::RED . " MURDER: " . TextFormat::YELLOW .  $this->murder->getName());
            $p->sendMessage(TextFormat::AQUA . " DETECTIVE: " . TextFormat::YELLOW .  $this->detective->getName());

         
        }
        // Remove Listener
        HandlerListManager::global()->unregisterAll($this->currentListener);
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
        $this->aliveLefts = count($players);
        // Pick Random Murder and Detective
        $this->pickRandom($this->players);

        // Create Scoreboard for all player
        foreach ($this->players as $player) {
            $player->setGamemode(GameMode::ADVENTURE());
            $player->getInventory()->clearAll();
            $rp = $mapCfg["spawnPoints"][array_rand($mapCfg["spawnPoints"])];
            ScoreBoardAPI::sendScore($player, TextFormat::BOLD . TextFormat::YELLOW . "MURDER");
            ScoreBoardAPI::setScoreLine($player, 1, TextFormat::WHITE . "Role: " . TextFormat::GREEN . " undefined");
            ScoreBoardAPI::setScoreLine($player, 2, TextFormat::WHITE . "Innocents Left: " . TextFormat::GREEN . " $this->aliveLefts");
            ScoreBoardAPI::setScoreLine($player, 3, TextFormat::WHITE . "Time Left: " . TextFormat::GREEN . " " . $this->timeLeft . "s");
            ScoreBoardAPI::setScoreLine($player, 4, TextFormat::WHITE . "Detective: " . TextFormat::GREEN . " Yes");
            ScoreBoardAPI::setScoreLine($player, 5, TextFormat::WHITE . "Score: " . TextFormat::GREEN . " undefined");
            ScoreBoardAPI::setScoreLine($player, 6, TextFormat::YELLOW . "OpenMM v0.0.1");
            $player->sendMessage(TextFormat::YELLOW . "The Murder Will have weapon in 5 seconds");
            if ($player->getId() == $this->murder->getId()) {
                $this->playSonud($player, "random.explode");
                $player->sendTitle(TextFormat::RED . "Murder", "Your goal is kill everyone");
                $sword =VanillaItems::IRON_SWORD();
                $enchant = new EnchantmentInstance(VanillaEnchantments::SWIFT_SNEAK(), 32767);
                $sword->setLore(["it's a weird knife."]);
                $sword->addEnchantment($enchant);
                $player->getInventory()->setItem(6 ,$sword);
                ScoreboardAPI::editLineScore($player, 1, "undefined", TextFormat::RED . " Murder");
            } else if ($player->getId()  == $this->detective->getId()) {
                $this->playSonud($player, "random.levelup");
                $player->sendMessage(TextFormat::YELLOW . "Your weapon will be weared in 5th inventory");
                $player->sendTitle(TextFormat::AQUA . "Detective", "Your goal is kill murder");
                $bow = VanillaItems::BOW();
                $bow->setLore([TextFormat::AQUA . " Detective Bow Pro Max"]);
                $enchant = new EnchantmentInstance(VanillaEnchantments::SILK_TOUCH(), 32767);
                $bow->addEnchantment($enchant);
                $player->getInventory()->addItem($bow);
                $player->getInventory()->addItem(VanillaItems::ARROW());
                ScoreboardAPI::editLineScore($player, 1, "undefined", TextFormat::AQUA . " Detective");
            } else {
                $this->playSonud($player, "random.explode");
                $player->sendMessage(TextFormat::YELLOW . "Your weapon will be weared in 5th inventory");
                $player->sendTitle(TextFormat::GREEN . "Innocents", "Survive.");
                ScoreboardAPI::editLineScore($player, 1, "undefined", TextFormat::GREEN . " Innocents");
            }
            $player->teleport(new Vector3($rp["x"], $rp["y"], $rp["z"]));
        }
        $murderPerSec = function () {
            $this->timeLeft = $this->timeLeft - 1;
            $this->updateSbData($this->timeLeft);
            if ($this->aliveLefts == 0) {
                $this->endRound("murder");
            }
            if ($this->timeLeft < 0) {
                $this->endRound("innocents");
            }
        };
        $spawnGodJob = function(){

        };
        $this->currentTask = $this->plugin->getScheduler()->scheduleRepeatingTask(new OpenMMTask($murderPerSec), 20);

        $this->currentListener = new MMRoundEvents($this);
        $this->plugin->getServer()->getPluginManager()->registerEvents($this->currentListener, $this->plugin);
    }
}
