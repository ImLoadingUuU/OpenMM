<?php
namespace MeTooIDK\OpenMM;

use pocketmine\event\Listener;

use pocketmine\event\player\PlayerDeathEvent;
class MMRoundOnDie implements Listener {
 public function __construct(private MMRound $round) {

 }
 public function onPlayerDeath(PlayerDeathEvent $event) {
   if ( $event->getPlayer() == $this->round->murder) {
     
   }
 }
}