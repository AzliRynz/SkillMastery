<?php

namespace KnosTx\SkillMastery;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerFishEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use jojoe77777\FormAPI\SimpleForm;

class Main extends PluginBase{

    private Config $playerData;
    private Config $skillsConfig;

    public function onEnable() : void{
        $this->saveResource("skills.yml");
        $this->skillsConfig = new Config($this->getDataFolder() . "skills.yml", Config::YAML);
        $this->playerData = new Config($this->getDataFolder() . "player_data.yml", Config::YAML);

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onDisable() : void{
        $this->playerData->save();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if ($command->getName() === "skills") {
            if ($sender instanceof Player) {
                $this->showSkillUI($sender);
                return true;
            }
            $sender->sendMessage("This command is only for players!");
        }
        return false;
    }

    private function showSkillUI(Player $player) : void{
        $name = $player->getName();
        $playerData = $this->playerData->get($name, ["xp" => 0, "skills" => []]);

        $form = new SimpleForm(function(Player $player, ?int $data){
            if ($data === null) return;

            $skills = array_keys($this->skillsConfig->get("skills", []));
            $selectedSkill = $skills[$data];
            $this->showSkillDetailUI($player, $selectedSkill);
        });

        $form->setTitle("Skill Mastery");
        $form->setContent("Select a skill to view details or upgrade:");
        foreach ($this->skillsConfig->get("skills", []) as $skill => $info) {
            $level = $playerData["skills"][$skill]["level"] ?? 0;
            $form->addButton(ucfirst($skill) . " (Level: $level)");
        }
        $player->sendForm($form);
    }

    private function showSkillDetailUI(Player $player, string $skill) : void{
        $name = $player->getName();
        $playerData = $this->playerData->get($name, ["xp" => 0, "skills" => []]);

        $currentLevel = $playerData["skills"][$skill]["level"] ?? 0;
        $xpNeeded = $this->skillsConfig->getNested("skills.$skill.levels")[$currentLevel + 1] ?? null;

        $form = new SimpleForm(function(Player $player, ?int $data) use ($skill){
            if ($data === null) return;

            if ($data === 0) $this->upgradeSkill($player, $skill);
        });

        $form->setTitle(ucfirst($skill));
        $form->setContent(
            "Current Level: $currentLevel\n" .
            "XP Needed for Next Level: " . ($xpNeeded ?? "Max Level")
        );
        $form->addButton("Upgrade Skill");
        $form->addButton("Back");
        $player->sendForm($form);
    }

    private function upgradeSkill(Player $player, string $skill) : void{
        $name = $player->getName();
        $playerData = $this->playerData->get($name, ["xp" => 0, "skills" => []]);

        $currentLevel = $playerData["skills"][$skill]["level"] ?? 0;
        $xpNeeded = $this->skillsConfig->getNested("skills.$skill.levels")[$currentLevel + 1] ?? null;

        if ($xpNeeded === null) {
            $player->sendMessage("This skill is at max level!");
            return;
        }

        if ($playerData["xp"] >= $xpNeeded) {
            $playerData["xp"] -= $xpNeeded;
            $playerData["skills"][$skill]["level"] = $currentLevel + 1;
            $this->playerData->set($name, $playerData);
            $this->playerData->save();
            $player->sendMessage("Your $skill skill has been upgraded to level " . ($currentLevel + 1) . "!");
        } else {
            $player->sendMessage("Not enough XP to upgrade this skill.");
        }
    }

    public function addXP(Player $player, int $amount) : void{
        $name = $player->getName();
        $playerData = $this->playerData->get($name, ["xp" => 0, "skills" => []]);

        $playerData["xp"] += $amount;
        $this->playerData->set($name, $playerData);
        $this->playerData->save();
        $player->sendMessage("You gained $amount XP!");
    }

    public function onBlockBreak(BlockBreakEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        $playerData = $this->playerData->get($name, ["xp" => 0, "skills" => []]);

        if (isset($playerData["skills"]["mining"])) {
            $level = $playerData["skills"]["mining"]["level"] ?? 0;
            $xpGain = 10 * ($level + 1);
            $this->addXP($player, $xpGain);
        }
    }

    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event) : void{
        $attacker = $event->getDamager();
        if ($attacker instanceof Player) {
            $name = $attacker->getName();
            $playerData = $this->playerData->get($name, ["xp" => 0, "skills" => []]);

            if (isset($playerData["skills"]["combat"])) {
                $level = $playerData["skills"]["combat"]["level"] ?? 0;
                $multiplier = 1 + $level * 0.1;
                $event->setModifier(
                    $event->getFinalDamage() * ($multiplier - 1),
                    EntityDamageByEntityEvent::MODIFIER_BASE
            );
        }
    }
    }

    public function onPlayerMove(PlayerMoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        $playerData = $this->playerData->get($name, ["xp" => 0, "skills" => []]);

        if (isset($playerData["skills"]["athletics"])) {
            $level = $playerData["skills"]["athletics"]["level"] ?? 0;
            $player->setMovementSpeed(0.1 + $level * 0.01);
        }
    }
}
