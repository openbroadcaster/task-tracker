<?php

class TaskTracker extends OBFController {

  public function addTask () {
    $manager  = $this->user->check_permission('task_tracker_module_manage');
    if (!$manager) {
      return [false, 'User does not have permission to add new tasks.'];
    }

    $task_model = $this->load->model('TaskTracker');
    $status     = [false, 'Failed to retrieve data from Task Tracker model.'];

    $data = [
      'name'        => $this->data('task_name'),
      'description' => $this->data('task_description'),
      'due'         => $this->data('task_due'),
      'users'       => $this->data('task_users'),
      'media'       => $this->data('task_media'),
      'playlists'   => $this->data('task_playlists'),
      'status'      => 'new'
    ];

    $status = $task_model('validate', $data);
    if (!$status[0]) {
      return $status;
    }

    $status = $task_model('addTask', $data);
    return $status;
  }

  public function loadUsers () {
    $manager = $this->user->check_permission('task_tracker_module_manage');
    if (!$manager) {
      return [false, 'User is not allowed to request list of users.'];
    }

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

    $data = [
      'sort_by'  => $this->data('sort_by'),
      'sort_dir' => $this->data('sort_dir')
    ];

    $result = $task_model('validateOverview', $data);
    if (!$result[0]) {
      return $result;
    }

    $manager = $this->user->check_permission('task_tracker_module_manage');
    if ($manager) {
      $result = $task_model('loadTaskOverview', $data);
    } else {
      $result = $task_model('loadTaskOverview', $data, $this->user->param('id'));
    }

    return $result;
  }

  public function viewTask () {
    $task_model = $this->load->model('TaskTracker');
    $result     = [false, 'Failed to retrieve data from Task Tracker model.'];
    $task_id    = $this->data('task_id');

    $data = [
      'task_id' => $task_id
    ];

    $manager  = $this->user->check_permission('task_tracker_module_manage');
    $assigned = $task_model('currentUserAssigned', $task_id);

    if (!$manager && !$assigned) {
      return [false, 'User is not allowed to view task'];
    }

    $result                     = $task_model('viewTask', $data);
    $result[2]['permissions']   = ($manager ? 'edit' : 'view');

    return $result;
  }

  public function removeTask () {
    $manager  = $this->user->check_permission('task_tracker_module_manage');
    if (!$manager) {
      return [false, 'User does not have permission to remove tasks.'];
    }

    $task_model = $this->load->model('TaskTracker');
    $result     = [false, 'Failed to retrieve data from Task Tracker model.'];

    $data = [
      'task_id' => $this->data('task_id')
    ];

    $result     = $task_model('removeTask', $data);
    return $result;
  }

  public function updateStatus () {
    $manager    = $this->user->check_permission('task_tracker_module_manage');
    $task_model = $this->load->model('TaskTracker');
    $assigned   = $task_model('currentUserAssigned', $this->data('task_id'));

    if (!$manager && !$assigned) {
        return [false, 'User does not have permission to update task status.'];
    }

    $result = [false, 'Failed to update task status'];
    $data = [
      'id'     => $this->data('task_id'),
      'status' => $this->data('task_status')
    ];

    $result = $task_model('validateStatus', $data);
    if (!$result[0]) {
      return $result;
    }

    $result = $task_model('updateTaskStatus', $data);
    return $result;
  }

  public function updateTask () {
    $manager  = $this->user->check_permission('task_tracker_module_manage');
    $task_model = $this->load->model('TaskTracker');
    $assigned = $task_model('currentUserAssigned', $this->data('task_id'));

    if (!$manager && !$assigned) {
      return [false, 'User does not have permission to update tasks.'];
    }

    if (!$manager) {
      $result     = [false, 'Failed to update task status'];
      $data = [
        'id'     => $this->data('task_id'),
        'status' => $this->data('task_status')
      ];

      $result = $task_model('validateStatus', $data);
      if (!$result[0]) {
        return $result;
      }

      $result = $task_model('updateTaskStatus', $data);
      return $result;
    }

    $result     = [false, 'Failed to retrieve data from Task Tracker model.'];

    $data = [
      'id'          => $this->data('task_id'),
      'name'        => $this->data('task_name'),
      'description' => $this->data('task_description'),
      'due'         => $this->data('task_due'),
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

  public function addComment () {
    $task_model = $this->load->model('TaskTracker');
    $task_id    = $this->data('task_id');

    $data = [
      'task_id' => $task_id,
      'comment' => $this->data('comment')
    ];

    $manager  = $this->user->check_permission('task_tracker_module_manage');
    $assigned = $task_model('currentUserAssigned', $task_id);
    if (!$manager && !$assigned) {
      return [false, 'User does not have permission to comment on current task'];
    }

    $status = $task_model('validateComment', $data);
    if (!$status[0]) {
      return $status;
    }

    $status = $task_model('addComment', $data);
    return $status;
  }
}
