<?php

class TaskTrackerModel extends OBFModel {
  
  public function validate ($data) {
    if ($data['name'] == '') return [false, 'Task name cannot be an empty string.'];
    
    return [true, 'Data is valid.'];
  }
  
  public function addTask ($data) {
    $task = [
      'name'        => $data['name'],
      'description' => $data['description'],
    ];
    $task_id = $this->db->insert('module_task_tracker', $task);
    
    foreach ($data['users'] as $user_item) {
      $user = [
        'task_id' => $task_id,
        'user_id' => $user_item
      ];
      $this->db->insert('module_task_tracker_users', $user);
    }
    
    foreach ($data['media'] as $media_item) {
      $media = [
        'task_id'   => $task_id,
        'media_id'  => $media_item
      ];
      $this->db->insert('module_task_tracker_media', $media);
    }
    
    foreach ($data['playlists'] as $playlist_item) {
      $playlist = [
        'task_id'     => $task_id,
        'playlist_id' => $playlist_item
      ];
      $this->db->insert('module_task_tracker_playlists', $playlist);
    }
    
    return [true, 'Successfully added task.'];
  }
  
  public function loadTaskOverview () {
    $this->db->orderby('id', 'desc');
    $tasks = $this->db->get('module_task_tracker');
        
    return [true, 'Successfully loaded tasks from database.', $tasks];
  }
  
  public function viewTask ($data) {
    $this->db->where('id', $data['task_id']);
    $task       = $this->db->get('module_task_tracker');
    
    if (!$task) {
      return [false, 'Failed to retrieve task from database.'];
    }

    $this->db->where('task_id', $data['task_id']);
    $user_items       = $this->db->get('module_task_tracker_users');
    $user_model       = $this->load->model('users');
    $users            = [];
    foreach ($user_items as $elem) {
      $user_item  = $user_model('get_by_id', $elem['user_id']);
      $users[]    = array(
        'id'      => $elem['id'],
        'task_id' => $elem['task_id'],
        'user'    => array(
          'id'           => $user_item['id'],
          'display_name' => $user_item['display_name']
        )
      );
    }
    
    $this->db->where('task_id', $data['task_id']);
    $media_items      = $this->db->get('module_task_tracker_media');
    $media_model      = $this->load->model('media');
    $media            = [];
    foreach ($media_items as $elem) {
      $media_item = $media_model('get_by_id', $elem['media_id']);
      $media[]    = array(
        'id'      => $elem['id'],
        'task_id' => $elem['task_id'],
        'media'   => array(
          'id'     => $media_item['id'],
          'artist' => $media_item['artist'],
          'title'  => $media_item['title']
        )
      );
    }
        
    $this->db->where('task_id', $data['task_id']);
    $playlists_items  = $this->db->get('module_task_tracker_playlists');
    $playlists_model  = $this->load->model('playlists');
    $playlists        = [];
    foreach ($playlists_items as $elem) {
      $playlist_item = $playlists_model('get_by_id', $elem['playlist_id']);
      $playlists[]   = array (
        'id'       => $elem['id'],
        'task_id'  => $elem['task_id'],
        'playlist' => array(
          'id'          => $playlist_item['id'],
          'name'        => $playlist_item['name'],
          'description' => $playlist_item['description']
        )
      );
    }
    
    $result = array(
      'task'      => $task[0],
      'users'     => $users,
      'media'     => $media,
      'playlists' => $playlists
    );
    return [true, 'Successfully loaded task from database.', $result];
  }
  
  public function removeTask ($data) {
    $this->db->where('id', $data['task_id']);
    $result = $this->db->delete('module_task_tracker');
    
    if (!$result) {
      return [false, 'Failed to remove task from database.'];
    }
    
    $this->db->where('task_id', $data['task_id']);
    $result = $this->db->delete('module_task_tracker_users');
    
    $this->db->where('task_id', $data['task_id']);
    $result = $this->db->delete('module_task_tracker_media');
    
    $this->db->where('task_id', $data['task_id']);
    $result = $this->db->delete('module_task_tracker_playlists');
    
    return [true, 'Successfully removed task from database.'];
  }
  
  public function updateTask ($data) {
    $task_id   = $data['id'];
    $task_data = array(
      'name'        => $data['name'],
      'description' => $data['description']
    );
    $task_users     = $data['users'];
    $task_media     = $data['media'];
    $task_playlists = $data['playlists'];
    
    $this->db->where('id', $task_id);
    if (!$this->db->get('module_task_tracker')) {
      return [false, 'Cannot find task in database.'];
    }
    
    $this->db->where('id', $task_id);
    if (!$this->db->update('module_task_tracker', $task_data)) {
      return [false, 'Failed to update task fields in database.'];
    }
    
    $this->db->where('task_id', $task_id);
    $this->db->delete('module_task_tracker_users');
    foreach ($task_users as $user_item) {
      $user = [
        'task_id'   => $task_id,
        'user_id'   => $user_item
      ];
      $this->db->insert('module_task_tracker_users', $user);
    }
    
    $this->db->where('task_id', $task_id);
    $this->db->delete('module_task_tracker_media');    
    foreach ($task_media as $media_item) {
      $media = [
        'task_id'   => $task_id,
        'media_id'  => $media_item
      ];
      $this->db->insert('module_task_tracker_media', $media);
    }
    
    $this->db->where('task_id', $task_id);
    $this->db->delete('module_task_tracker_playlists');
    foreach ($task_playlists as $playlist_item) {
      $playlist = [
        'task_id'     => $task_id,
        'playlist_id' => $playlist_item
      ];
      $this->db->insert('module_task_tracker_playlists', $playlist);
    }
    
    return [true, 'Successfully updated task'];
  }
}