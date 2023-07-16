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

class Main extends PluginBase
{
    private $config;
    private $maps;

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
        $this->maps = array();
        $this->config->save();
    }
    private function applyConfig()
    {
        $this->config->set("maps",$this->maps);
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
        $this->maps = $config->get("maps");
        if (isset($this->maps)) {
            $this->getLogger()->info(TextFormat::GREEN . "OpenMM Loaded " . count($this->maps) . " Maps");
        }  else {
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
                    $sender->sendMessage("§l§cPlease enter a subcommand");
                    return false;
                }
                switch (strtolower($args[0])) {
                    case "newmap":
                        if (!isset($args[1])) {
                            $sender->sendMessage("§l§cPlease enter a name");
                            return false;
                        }
                        $worldId = null;
                        
                        try {
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
                            if (isset($this->maps[$worldId])) {
                                $sender->sendMessage(TextFormat::RED . "This world is already in the database");
                                return true;
                            }
                            $sender->sendMessage(TextFormat::AQUA . "Selected World - World ID:" . $worldId);
                            if ($this->getServer()->getWorldManager()->getWorld($worldId) == null) {
                                $sender->sendMessage(TextFormat::RED . "World not found");
                                return false;
                            }
                            $this->maps[$worldId] = array(
                                "name" => $args[1],
                                "spawnPoints" => []
                            );
                            $this->applyConfig();
                         } catch (Exception $err) {
                            $sender->sendMessage(TextFormat::RED . TextFormat::BOLD . "OpenMM Error: " . $err->getMessage());
                         }
                        return true;
                    default:
                        return false;
                }

              
            default:
                throw new \AssertionError("This line will never be executed");
        }
    }
}
