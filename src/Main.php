<?php

declare(strict_types=1);

namespace MeTooIDK\OpenMM;

use Exception;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\form\Form;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;

class Main extends PluginBase
{
    public $config;
    public $maps_data;

    private function setupCfg()
    {
        // 設定基本設置檔案
        $this->config->set("maps", array());
        /**
         * 想法 <MAP ITEM>
         * maps => name = String
         *      => spawnPoints = array
         * 
         */
        //
        $this->maps_data = array();
        $this->config->save();
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
    private function applyConfig()
    {
        $this->config->set("maps", $this->maps_data);
        $this->config->save();
        $this->getLogger()->info("Applyed Config");
    }
    public function onLoad(): void
    {
        $this->getLogger()->info("Loading OpenMM");
    }
    public function onEnable(): void
    {
        @mkdir($this->getDataFolder());
        $this->saveResource("maps.json");
        $config = new Config($this->getDataFolder() . "maps_data.json", Config::JSON);
        $this->config = $config;
        $this->maps_data = $config->get("maps");
        if (isset($this->maps_data)) {
            $this->getLogger()->info(TextFormat::GREEN . "OpenMM Loaded " . count($this->maps_data) . " Maps");
        } else {
            $this->getLogger()->info(TextFormat::YELLOW . "OpenMM Configuration Not Loaded.");
            $this->setupCfg();
        }
        $this->getLogger()->info("Creating Database File");
        $this->getLogger()->info("OpenMM Enabled");
    }

    // command
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {

        switch ($command->getName()) {

            case "openmm":
                if (!isset($args[0])) {
                    $sender->sendMessage("§l§cPlease enter a subcommand (newmap,start,set_map_spawn");
                    return false;
                }
                try {
                 
                    switch (strtolower($args[0])) {
                        case "set_map_spawn":
                            $worldId = null;
                            $x = null;
                            $y = null;
                            $z = null;
                            if ($sender instanceof Player) {
                                $worldId =  $sender->getWorld()->getId();
                                if (!isset($this->maps_data[$worldId])) throw new Exception("Map Not Found");
                                $x = $sender->getPosition()->getX();
                                $y = $sender->getPosition()->getY();
                                $z = $sender->getPosition()->getZ();
                                array_push($this->maps_data[$worldId]["spawnPoints"], array(
                                    "x" => $x,
                                    "y" => $y,
                                    "z" => $z
                                ));
                                $sender->sendMessage(TextFormat::GREEN . " Created Spawn at {$x},{$y},{$z}");
                                $sender->sendActionBarMessage(TextFormat::GREEN . " Created Spawn at {$x},{$y},{$z}");
                                $this->playSonud($sender, "random.orb");
                                $this->applyConfig();
                                return true;
                            } else {
                                $sender->sendMessage(TextFormat::YELLOW . " Please use this command in game!");
                                return false;
                            };

                        case "newmap":
                            if (!isset($args[1])) {
                                $sender->sendMessage("§l§cPlease enter a name");
                                return false;
                            }
                            $worldId = null;
                            if ($sender instanceof Player) {
                                $worldId =  $sender->getWorld()->getId();
                            };
                            if (isset($args[2])) {
                                $world = $this->getServer()->getWorldManager()->getWorldByName($args[2]);
                                if (!isset($world)) {
                                    throw new Exception("World Not Found");
                                }
                                $worldId =  $world->getId();
                            }
                            if (!isset($args[2])) {
                                $sender->sendMessage(TextFormat::RED . "Please enter a world id");
                                return false;
                            }
                            if (isset($this->maps_data[$worldId])) {
                                $sender->sendMessage(TextFormat::RED . "This world is already in the database");
                                return true;
                            }
                            $sender->sendMessage(TextFormat::AQUA . "Selected World - World ID:" . $worldId);
                            if ($this->getServer()->getWorldManager()->getWorld($worldId) == null) {
                                $sender->sendMessage(TextFormat::RED . "World not found");
                                return false;
                            }
                            $this->maps_data[$worldId] = array(
                                "name" => $args[1],
                                "spawnPoints" => []
                            );
                            $this->applyConfig();
                            return true;
                        case "start":
                            try {

                                $worldId = null;
                                if ($sender instanceof Player) {
                                    $worldId =  $sender->getWorld()->getId();
                                };
                                if (isset($args[1])) {
                                    $world = $this->getServer()->getWorldManager()->getWorldByName($args[1]);
                                    if (!isset($world)) {
                                        throw new Exception("World Not Found");
                                    }
                                    $worldId =  $world->getId();
                                }
                                if (!isset($worldId)) throw new Exception("Unknown World");
                                $round = new MMRound($this, $worldId);
                                $round->startRound();
                            } catch (Exception $err) {
                                $sender->sendMessage(TextFormat::RED . TextFormat::BOLD . "OpenMM Error: " . $err->getMessage());
                                $sender->sendMessage("Line {$err->getLine()} - {$err->getFile()}");
                            }
                            return true;
                        default:
                            return false;
                    }
                } catch (Exception $err) {
                    $sender->sendMessage(TextFormat::RED . TextFormat::BOLD . "OpenMM Error: " . $err->getMessage());
                }
            default:
                throw new \AssertionError("This line will never be executed");
        }
    }
}
