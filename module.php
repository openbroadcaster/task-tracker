<?php

class TaskTrackerModule extends OBFModule {

  public $name        = "Task Tracker v1.0";
  public $description = "Tracks tasks with assigned playlists and media items.";

  public function callbacks () {

  }

  public function install () {
    $this->db->insert('users_permissions', [
      'category'    => 'task tracker',
      'description' => 'manage tasks',
      'name'        => 'task_tracker_module_manage'
    ]);

    $this->db->query('CREATE TABLE IF NOT EXISTS `module_task_tracker` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `name` varchar(255) NOT NULL,
      `description` text,
      `created` int(11) unsigned NOT NULL,
      `due` int(11) unsigned,
      `status` enum("new", "in progress", "complete") default "new" NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

    $this->db->query('CREATE TABLE IF NOT EXISTS `module_task_tracker_media` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `task_id` int(10) unsigned NOT NULL,
      `media_id` int(11) unsigned NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

    $this->db->query('CREATE TABLE IF NOT EXISTS `module_task_tracker_playlists` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `task_id` int(10) unsigned NOT NULL,
      `playlist_id` int(11) unsigned NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

    $this->db->query('CREATE TABLE IF NOT EXISTS `module_task_tracker_users` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `task_id` int(10) unsigned NOT NULL,
      `user_id` int(11) unsigned NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

    $this->db->query('CREATE TABLE IF NOT EXISTS
      `module_task_tracker_comments` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `task_id` int(10) unsigned NOT NULL,
      `user_id` int(11) unsigned NOT NULL,
      `comment` text,
      `created` int(11) unsigned NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

    // Add task payment info table.
    $this->db->query('CREATE TABLE IF NOT EXISTS `module_task_tracker_payments` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `task_id` int(10) unsigned NOT NULL,
      `amount` decimal(13, 2) NOT NULL,
      `type` ENUM(\'complete\', \'media\', \'playlist\', \'mediaplaylist\', \'other\') NOT NULL,
      `comment` text,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    return true;
  }

  public function uninstall () {
    $this->db->where('name','task_tracker_module_manage');
    $permission = $this->db->get_one('users_permissions');

    $this->db->where('permission_id', $permission['id']);
    $this->db->delete('users_permissions_to_groups');

    $this->db->where('id', $permission['id']);
    $this->db->delete('users_permissions');

    // Keep tables for now.
    // $this->db->query('DROP TABLE `module_task_tracker`');

    return true;
  }
}
