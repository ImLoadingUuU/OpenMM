<?php

namespace MeTooIDK\OpenMM;

use AttachableLogger;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;

use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\scheduler\TaskHandler;
use pocketmine\utils\TextFormat;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\HandlerList;
use pocketmine\event\HandlerListManager;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\player\GameMode;

class MMRoundEvents implements Listener
{
  private TaskHandler $currentBowTask;
  private $round;
  public function __construct(MMRound &$round)
  {
    $this->round = $round;
  }
  public function onPlayerRespawn(PlayerRespawnEvent $event)
  {
    $event->getPlayer()->sendMessage(TextFormat::GRAY . "You are dead");
    $event->getPlayer()->setGamemode(GameMode::SPECTATOR());
  }
  public function onPlayerDeath(PlayerDeathEvent $event)
  {
    $this->round->aliveLefts = $this->round->aliveLefts -  1;
    foreach ($this->round->players as $p) {
      $p->sendMessage(TextFormat::YELLOW . "A Player Has Dead");
    }
    if ($event->getPlayer() == $this->round->detective) {
      foreach ($this->round->players as $p) {
        $p->sendMessage(TextFormat::YELLOW . "Detective has been killed");
      }
      $this->round->detectiveAlive = false;;
    }

    if ($event->getPlayer() == $this->round->murder) {
      $this->round->murderAlive = false;
      $this->round->endRound("detective");
    }
  }
  public function onEntityDamageByEntity(EntityDamageByEntityEvent $event)
  {
    $this->round->plugin->getLogger()->info("onEntityDamageByEntity Triggered");
    $damager = $event->getDamager();
    $cause = $event->getCause();
    if ($damager instanceof Player) {
      if ($cause == 2) {
        $event->getEntity()->setHealth(0);
        if ($event->getEntity() !== $this->round->detective or $event->getEntity() !== $this->round->murder) {
          $event->getDamager()->setHealth(0);
        }
      }
      if ($damager->getInventory()->getItemInHand() == VanillaItems::IRON_SWORD()) {
        $event->getDamager()->setHealth(0);
      } 
      $event->cancel();
    }
  }
  public function onShoot(EntityShootBowEvent $event)
  {
    $player = $event->getEntity();
    if ($player instanceof Player) {
      $tl = 1;
      $func = function () use (&$tl, $player) {
        $filled = '';
        $unfilled = '';
        if ($tl >= 10) {
          $player->getInventory()->addItem(VanillaItems::ARROW());
          $player->sendActionBarMessage("READY - "  . TextFormat::BOLD . " [" . TextFormat::GREEN . $filled . TextFormat::YELLOW . $unfilled .  TextFormat::WHITE . TextFormat::BOLD . "] " . TextFormat::BOLD . $tl . "s");
          $this->currentBowTask->cancel();
        } else {
          $tl = $tl + 1;
          $filled = str_repeat("=", $tl);
          $unfilled = str_repeat("-", 10 - $tl);
          $player->sendActionBarMessage("Refilling - "  . TextFormat::BOLD . " [" . TextFormat::GREEN . $filled . TextFormat::YELLOW . $unfilled .  TextFormat::WHITE . TextFormat::BOLD . "] " . TextFormat::BOLD . $tl . "s");
        }
      };
      $this->currentBowTask = $this->round->plugin->getScheduler()->scheduleRepeatingTask(new OpenMMTask($func), 20);
    }
  }
}
