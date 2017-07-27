<?php

$redis_ksys_config['static']['static_battle_anim']['primary_key'] = 'anim_id';
$redis_ksys_config['static']['static_battle_anim']['type'] = 'hash';

$redis_ksys_config['static']['static_battle_base']['primary_key'] = 'battle_id';
$redis_ksys_config['static']['static_battle_base']['type'] = 'hash';

$redis_ksys_config['static']['static_battle_state']['primary_key'] = 'state_id';
$redis_ksys_config['static']['static_battle_state']['type'] = 'hash';

$redis_ksys_config['static']['static_build_base']['primary_key'] = ['build_type','level'];
$redis_ksys_config['static']['static_build_base']['type'] = 'hash';

$redis_ksys_config['static']['static_draw_base']['primary_key'] = 'draw_id';
$redis_ksys_config['static']['static_draw_base']['type'] = 'hash';

$redis_ksys_config['static']['static_draw_poll']['primary_key'] = 'poll_id';
$redis_ksys_config['static']['static_draw_poll']['type'] = 'hash';


$redis_ksys_config['static']['static_drop_base']['primary_key'] = 'drop_id';
$redis_ksys_config['static']['static_drop_base']['type'] = 'hash';

$redis_ksys_config['static']['static_enemy_base']['primary_key'] = 'enemy_id';
$redis_ksys_config['static']['static_enemy_base']['type'] = 'hash';

$redis_ksys_config['static']['static_equip_base']['primary_key'] = 'equip_id';
$redis_ksys_config['static']['static_equip_base']['type'] = 'hash';

$redis_ksys_config['static']['static_equip_strengthen_upgrade']['primary_key'] = 'level_id';
$redis_ksys_config['static']['static_equip_strengthen_upgrade']['type'] = 'hash';



$redis_ksys_config['static']['static_game_config']['primary_key'] = 'config_name';
$redis_ksys_config['static']['static_game_config']['type'] = 'hash';

$redis_ksys_config['static']['static_item_base']['primary_key'] = 'item_id';
$redis_ksys_config['static']['static_item_base']['type'] = 'hash';

$redis_ksys_config['static']['static_map_base']['primary_key'] = 'map_id';
$redis_ksys_config['static']['static_map_base']['type'] = 'hash';

$redis_ksys_config['static']['static_map_explore']['primary_key'] = ['map_id','explore_id'];
$redis_ksys_config['static']['static_map_explore']['type'] = 'hash';

$redis_ksys_config['static']['static_map_event']['primary_key'] = ['explore_id','event_id'];
$redis_ksys_config['static']['static_map_event']['type'] = 'hash';

$redis_ksys_config['static']['static_role_base']['primary_key'] = 'role_id';
$redis_ksys_config['static']['static_role_base']['type'] = 'hash';

$redis_ksys_config['static']['static_role_evolution_upgrade']['primary_key'] = ['element','rarity','level_id'];
$redis_ksys_config['static']['static_role_evolution_upgrade']['type'] = 'hash';

$redis_ksys_config['static']['static_role_strengthen_upgrade']['primary_key'] = 'level_id';
$redis_ksys_config['static']['static_role_strengthen_upgrade']['type'] = 'hash';


$redis_ksys_config['static']['static_skill_actor_effect']['primary_key'] = 'actor_effect_id';
$redis_ksys_config['static']['static_skill_actor_effect']['type'] = 'hash';

$redis_ksys_config['static']['static_skill_base']['primary_key'] = 'skill_id';
$redis_ksys_config['static']['static_skill_base']['type'] = 'hash';

$redis_ksys_config['static']['static_skill_sub']['primary_key'] = 'sub_skill_id';
$redis_ksys_config['static']['static_skill_sub']['type'] = 'hash';


$redis_ksys_config['static']['static_store_base']['primary_key'] = 'goods_id';
$redis_ksys_config['static']['static_store_base']['type'] = 'hash';


$redis_ksys_config['static']['static_trump_base']['primary_key'] = 'trump_id';
$redis_ksys_config['static']['static_trump_base']['type'] = 'hash';

$redis_ksys_config['static']['static_trump_evolution_upgrade']['primary_key'] = ['rarity','level_id'];
$redis_ksys_config['static']['static_trump_evolution_upgrade']['type'] = 'hash';

$redis_ksys_config['static']['static_trump_strengthen_upgrade']['primary_key'] = 'level_id';
$redis_ksys_config['static']['static_trump_strengthen_upgrade']['type'] = 'hash';

$redis_ksys_config['static']['static_trump_skill_strengthen_upgrade']['primary_key'] = ['rarity','level_id'];
$redis_ksys_config['static']['static_trump_skill_strengthen_upgrade']['type'] = 'hash';

$redis_ksys_config['static']['static_user_level_upgrade']['primary_key'] = 'level_id';
$redis_ksys_config['static']['static_user_level_upgrade']['type'] = 'hash';



return $redis_ksys_config;