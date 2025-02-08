# SkillMastery
This plugin that introduces a skill system, allowing players to level up and improve their abilities in areas like **Mining**, **Combat**, and **Athletics**. This plugin enhances gameplay by adding an RPG-like progression system.

## Features

- **Skill Leveling**: Players can level up their skills and unlock bonuses.
- **XP System**: Gain XP through activities such as breaking blocks, combat, and movement.
- **Dynamic Effects**:
  - **Mining**: Earn extra XP based on skill level when breaking blocks.
  - **Combat**: Increased damage multiplier as skill level increases.
  - **Athletics**: Enhanced movement speed with higher skill levels.
- **Interactive Skill Menu**: View skill progress and upgrade levels via a user-friendly UI.

## Commands

- `/skills`: Open the skill menu to view or upgrade skills (player-only command).

## Configuration

- `skills.yml`: Define skills, levels, and XP requirements.  
  Example format:
  ```yaml
  skills:
    mining:
      levels:
        1: 100
        2: 250
        3: 500
    combat:
      levels:
        1: 200
        2: 400
        3: 800
    athletics:
      levels:
        1: 150
        2: 300
        3: 600
