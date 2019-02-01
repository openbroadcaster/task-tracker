<?php

class TaskTrackerModule extends OBFModule {
  
  public $name        = "Task Tracker v0.1";
  public $description = "Tracks tasks with assigned playlists and media items.";
  
  public function callbacks () {
    
  }
  
  public function install () {
    
    $this->db->query('CREATE TABLE IF NOT EXISTS `module_task_tracker` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `name` varchar(255) NOT NULL,
      `description` text,
      PRIMARY KEY (`id`)
    ) ENGINE MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;');
    
    $this->db->query('CREATE TABLE IF NOT EXISTS `module_task_tracker_media` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `task_id` int(10) unsigned NOT NULL,
      `media_id` int(11) unsigned NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;');
    
    $this->db->query('CREATE TABLE IF NOT EXISTS `module_task_tracker_playlists` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `task_id` int(10) unsigned NOT NULL,
      `playlist_id` int(11) unsigned NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;');
    
    $this->db->query('CREATE TABLE IF NOT EXISTS `module_task_tracker_users` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `task_id` int(10) unsigned NOT NULL,
      `user_id` int(11) unsigned NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;');
    
    $this->db->query('CREATE TABLE IF NOT EXISTS
      `module_task_tracker_comments` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `task_id` int(10) unsigned NOT NULL,
      `user_id` int(11) unsigned NOT NULL,
      `comment` text,
      PRIMARY KEY (`id`)
    ) ENGINE MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;');
    
    return true;
  }
  
  public function uninstall () {
    
    // Keep tables for now.
    // $this->db->query('DROP TABLE `module_task_tracker`');
    
    return true;
  }
}