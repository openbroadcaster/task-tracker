<?php

class TaskTracker extends OBFController {
  
  public function addTask () {
    $task_model = $this->load->model('TaskTracker');
    $status     = [false, 'Failed to retrieve data from Task Tracker model.'];
    
    $data = [
      'name'        => $this->data('task_name'),
      'description' => $this->data('task_description'),
      'users'       => $this->data('task_users'),
      'media'       => $this->data('task_media'),
      'playlists'   => $this->data('task_playlists')
    ];
    
    $status = $task_model('validate', $data);
    if (!$status[0]) {
      return $status;
    } 
    
    $status = $task_model('addTask', $data);
    return $status;
  }
  
  public function loadUsers () {
    $users_model = $this->load->model('users');
    $users       = $users_model('user_list');
    
    if (!$users) {
      return [false, 'Unable to load any users from database.'];
    }
    
    $result      = [true, 'Loaded users successfully.', $users];    
    return $result;
  }
  
  public function loadTaskOverview () {
    $task_model = $this->load->model('TaskTracker');
    $result     = [false, 'Failed to retrieve data from Task Tracker model.'];
    
    $result     = $task_model('loadTaskOverview');    
    return $result;
  }
  
  public function viewTask () {
    $task_model = $this->load->model('TaskTracker');
    $result     = [false, 'Failed to retrieve data from Task Tracker model.'];
    
    $data = [
      'task_id' => $this->data('task_id')
    ];
    
    $result                     = $task_model('viewTask', $data);
    $result[2]['permissions']   = 'view'; // TODO, dynamic permissions
    return $result;
  }
  
  public function removeTask () {
    $task_model = $this->load->model('TaskTracker');
    $result     = [false, 'Failed to retrieve data from Task Tracker model.'];
    
    $data = [
      'task_id' => $this->data('task_id')
    ];
    
    $result     = $task_model('removeTask', $data);
    return $result;
  }
  
  public function updateTask () {
    $task_model = $this->load->model('TaskTracker');
    $result     = [false, 'Failed to retrieve data from Task Tracker model.'];
    
    $data = [
      'id'          => $this->data('task_id'),
      'name'        => $this->data('task_name'),
      'description' => $this->data('task_description'),
      'users'       => $this->data('task_users'),
      'media'       => $this->data('task_media'),
      'playlists'   => $this->data('task_playlists')
    ];
    
    $result = $task_model('validate', $data);
    if (!$result[0]) {
      return $result;
    }
    
    $result = $task_model('updateTask', $data);    
    return $result;
  }
}