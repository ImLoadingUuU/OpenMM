<?php

namespace MeTooIDK\OpenMM;



class MMRound
{
    public function __construct(private Main $plugin)
    {
    }
    public function startRound(int $worldId)
    {
        $this->plugin->getLogger()->info("Starting Round");
        $world = $this->plugin->getServer()->getWorldManager()->getWorld($worldId);
        $players = $world->getPlayers();
        
    }
}
