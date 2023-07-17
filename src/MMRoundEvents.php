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
use pocketmine\event\HandlerList;
use pocketmine\event\HandlerListManager;
use pocketmine\event\player\PlayerInteractEvent;

class MMRoundEvents implements Listener {
  private TaskHandler $currentBowTask;
 public function __construct(private MMRound $round) {
   
 }
 
 public function onPlayerDeath(PlayerDeathEvent $event) {
  foreach ($this->round->players as $p) {
    $p->sendMessage(TextFormat::YELLOW . " A Player Has Dead");
    
  }

   if ( $event->getPlayer() == $this->round->detective) {
    
     foreach ($this->round->players as $p) {
      $p->sendMessage(TextFormat::YELLOW . "Detective has been killed");
     }
     $this->round->detectiveAlive = false;;
   }
   if ($event->getPlayer() == $this->round->murder) {
    $this->round->murderAlive = false;
    $this->round->endRound();
   }
 }
 public function onEntityDamageByEntity(EntityDamageByEntityEvent $event) {
  $this->round->plugin->getLogger()->info("onEntityDamageByEntity Triggered");
   if ($event->getDamager() instanceof Player) {
     if ($event->getDamager() !== $this->round->murder) {
      $event->cancel();
      return;
     }
     $event->getEntity()->setHealth(0);

    

   }  
  
 }
 public function onShoot(EntityShootBowEvent $event) {
  $player = $event->getEntity();
  if ($player instanceof Player) {
    $tl = 0;
    $func = function() use(&$tl,$player){
      $filled = '';
      $unfilled = '';
      if ($tl > 0) {
        $filled = str_repeat("=", $tl);
        $unfilled = str_repeat("-", 10 - $tl);
      }
      $player->sendActionBarMessage("Refilling - "  . TextFormat::BOLD. " [" . TextFormat::GREEN . $filled . TextFormat::YELLOW . $unfilled .  TextFormat::WHITE . TextFormat::BOLD . "] ". TextFormat::BOLD . $tl . "s") ;
      if ($tl > 10) {
        $player->getInventory()->addItem(VanillaItems::ARROW());
        $this->currentBowTask->cancel();
      } else {
        $tl = $tl + 1;
      }
    };
    $this->currentBowTask = $this->round->plugin->getScheduler()->scheduleRepeatingTask(new OpenMMTask($func), 20);
    
  }
 }
}