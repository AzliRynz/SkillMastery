<?php

declare(strict_types=1);

namespace KnosTx\SkillMastery;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use function array_keys;
use function ucfirst;

class Main extends PluginBase implements Listener{
	private Config $playerData;

	private Config $skillsConfig;

	/**
	 * Called when the plugin is enabled.
	 */
	public function onEnable() : void{
		$this->saveResource("skills.yml");
		$this->skillsConfig = new Config($this->getDataFolder() . "skills.yml", Config::YAML);
		$this->playerData = new Config($this->getDataFolder() . "player_data.yml", Config::YAML);

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * Called when the plugin is disabled.
	 */
	public function onDisable() : void{
		$this->playerData->save();
	}

	/**
	 * Handles the execution of commands.
	 */
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if($command->getName() === "skills"){
			if($sender instanceof Player){
				$this->showSkillUI($sender);
				return true;
			}
			$sender->sendMessage("This command is only for players!");
		}
		return false;
	}

	/**
	 * Displays the skill selection UI to the player.
	 */
	private function showSkillUI(Player $player) : void{
		$name = $player->getName();
		$playerData = $this->playerData->get($name, ["xp" => 0, "skills" => []]);

		$form = new SimpleForm(function(Player $player, ?int $data){
			if($data === null) return;

			$skills = array_keys($this->skillsConfig->get("skills", []));
			$selectedSkill = $skills[$data];
			$this->showSkillDetailUI($player, $selectedSkill);
		});

		$form->setTitle("Skill Mastery");
		$form->setContent("Select a skill to view details or upgrade:");
		foreach($this->skillsConfig->get("skills", []) as $skill => $info){
			$level = $playerData["skills"][$skill]["level"] ?? 0;
			$form->addButton(ucfirst($skill) . " (Level: $level)");
		}
		$player->sendForm($form);
	}

	/**
	 * Displays the details of a specific skill and the option to upgrade it.
	 */
	private function showSkillDetailUI(Player $player, string $skill) : void{
		$name = $player->getName();
		$playerData = $this->playerData->get($name, ["xp" => 0, "skills" => []]);

		$currentLevel = $playerData["skills"][$skill]["level"] ?? 0;
		$xpNeeded = $this->skillsConfig->getNested("skills.$skill.levels")[$currentLevel + 1] ?? null;

		$form = new SimpleForm(function(Player $player, ?int $data) use($skill){
			if($data === null) return;

			if($data === 0) $this->upgradeSkill($player, $skill);
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

	/**
	 * Attempts to upgrade the specified skill for the player.
	 */
	private function upgradeSkill(Player $player, string $skill) : void{
		$name = $player->getName();
		$playerData = $this->playerData->get($name, ["xp" => 0, "skills" => []]);

		$currentLevel = $playerData["skills"][$skill]["level"] ?? 0;
		$xpNeeded = $this->skillsConfig->getNested("skills.$skill.levels")[$currentLevel + 1] ?? null;

		if($xpNeeded === null){
			$player->sendMessage("This skill is at max level!");
			return;
		}

		if($playerData["xp"] >= $xpNeeded){
			$playerData["xp"] -= $xpNeeded;
			$playerData["skills"][$skill]["level"] = $currentLevel + 1;
			$this->playerData->set($name, $playerData);
			$this->playerData->save();
			$player->sendMessage("Your $skill skill has been upgraded to level " . ($currentLevel + 1) . "!");
		} else{
			$player->sendMessage("Not enough XP to upgrade this skill.");
		}
	}

	/**
	 * Adds XP to the player.
	 */
	public function addXP(Player $player, int $amount) : void{
		$name = $player->getName();
		$playerData = $this->playerData->get($name, ["xp" => 0, "skills" => []]);

		$playerData["xp"] += $amount;
		$this->playerData->set($name, $playerData);
		$this->playerData->save();
		$player->sendMessage("You gained $amount XP!");
	}

	/**
	 * Handles the BlockBreakEvent to grant XP for mining.
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		$player = $event->getPlayer();
		$name = $player->getName();
		$playerData = $this->playerData->get($name, ["xp" => 0, "skills" => []]);
		
		if(isset($playerData["skills"]["mining"])){
			$level = $playerData["skills"]["mining"]["level"] ?? 0;
			$xpGain = 10 * ($level + 1);
			$this->addXP($player, $xpGain);
		}
	}

	/**
	 * Handles the EntityDamageByEntityEvent to increase combat damage.
	 */
	public function onEntityDamageByEntity(EntityDamageByEntityEvent $event) : void{
		$attacker = $event->getDamager();
		if($attacker instanceof Player){
			$name = $attacker->getName();
			$playerData = $this->playerData->get($name, ["xp" => 0, "skills" => []]);

			if(isset($playerData["skills"]["combat"])){
				$level = $playerData["skills"]["combat"]["level"] ?? 0;
				$multiplier = 1 + $level * 0.1;
				$baseDamage = $event->getBaseDamage();
				$event->setBaseDamage($baseDamage * $multiplier);
			}
		}
		
		public function onPlayerMove(PlayerMoveEvent $event) : void{
		$player = $event->getPlayer();
		$name = $player->getName();
		$playerData = $this->playerData->get($name, ["xp" => 0, "skills" => []]);

		if(isset($playerData["skills"]["athletics"])){
			$level = $playerData["skills"]["athletics"]["level"] ?? 0;
			$player->setMovementSpeed(0.1 + $level * 0.01);
		}
	}
}
