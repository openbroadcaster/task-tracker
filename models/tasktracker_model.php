<?php

class TaskTrackerModel extends OBFModel {

  public function validate ($data) {
    if ($data['name'] == '') {
      return [false, 'Task name cannot be an empty string.'];
    }

    $task_due = $data['due'];
    if ($task_due != '' && !preg_match('/^\d{4}-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$/', $task_due)) {
      return [false, 'Invalid due date provided.'];
    }

    $models = OBFModels::get_instance();
    foreach ($data['media'] as $media_id) {
      if ($models->media('get_by_id', ['id' => $media_id])['status'] != 'public') {
        return [false, 'Private media cannot be used in tasks.'];
      }
    }

    $playlists_model = $this->load->model('playlists');
    foreach ($data['playlists'] as $playlist_id) {
      if ($playlists_model('get_by_id', $playlist_id)['status'] != 'public') {
        return [false, 'Private playlists cannot be used in tasks.'];
      }
    }

    if (isset($data['payment_item'])) {
      $item = $data['payment_item'];
      if ($item != 'complete' && $item != 'media' && $item != 'playlist'
      && $item != 'mediaplaylist' && $item != 'other') {
        return [false, 'Invalid payment item type.'];
      }

      if ($data['payment_amount'] < 0) {
        return [false, 'Payment amount cannot be negative.'];
      }
    }

    return [true, 'Data is valid.'];
  }

  public function addTask ($data) {
    $task = [
      'name'        => $data['name'],
      'description' => OBFHelpers::sanitize_html($data['description']),
      'created'     => time()
    ];
    $task_due = $data['due'];
    if ($task_due != '') $task['due'] = strtotime($task_due . ' 12:00');

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

    if (isset($data['payment_item'])) {
      $comment = (isset($data['payment_comment']) ? $data['payment_comment'] : '');
      $payment = [
        'task_id'     => $task_id,
        'amount'      => $data['payment_amount'],
        'type'        => $data['payment_item'],
        'comment'     => $comment
      ];
      $this->db->insert('module_task_tracker_payments', $payment);
    }

    return [true, 'Successfully added task.'];
  }

  public function validateOverview ($data) {
    $sort_by  = $data['sort_by'];
    $sort_dir = $data['sort_dir'];
    $valid    = array('name', 'description', 'created', 'due', 'status');

    if (!in_array($sort_by, $valid)) {
      return [false, 'Invalid value to sort by.'];
    }

    if ($sort_dir != 'asc' && $sort_dir != 'desc') {
      return [false, 'Invalid sort direction.'];
    }

    return [true, 'Data is valid.'];
  }

  public function loadTaskOverview ($data, $user_id = null) {
    $sort_by  = $data['sort_by'];
    $sort_dir = $data['sort_dir'];

    $this->db->orderby('module_task_tracker.' . $sort_by, $sort_dir);

    if ($user_id !== null) {
      $this->db->leftjoin('module_task_tracker_users', 'module_task_tracker_users.task_id', 'module_task_tracker.id');
      $this->db->where('module_task_tracker_users.user_id', $user_id);
      $this->db->what('module_task_tracker.id');
      $this->db->what('description');
      $this->db->what('status');
      $this->db->what('name');
      $this->db->what('created');
      $this->db->what('due');
    }

    $tasks = $this->db->get('module_task_tracker');

    foreach ($tasks as $i => $task) {
      $this->db->where('task_id', $task['id']);
      $this->db->what('user_id');
      $assigned = $this->db->get('module_task_tracker_users');

      $task['assigned'] = array();
      foreach ($assigned as $assigned_user) {
        $task['assigned'][] = $assigned_user['user_id'];
      }

      $tasks[$i] = $task;
    }

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
    $users            = [];
    foreach ($user_items as $elem) {
      $users[] = $elem['user_id'];
    }

    $this->db->where('task_id', $data['task_id']);
    $media_items      = $this->db->get('module_task_tracker_media');
    $models           = OBFModels::get_instance();
    $media            = [];
    foreach ($media_items as $elem) {
      if ($models->media('get_by_id', ['id' => $elem['media_id']])['status'] == 'public') {
        $media[] = $elem['media_id'];
      }
    }

    $this->db->where('task_id', $data['task_id']);
    $playlists_items  = $this->db->get('module_task_tracker_playlists');
    $playlists_model  = $this->load->model('playlists');
    $playlists        = [];
    foreach ($playlists_items as $elem) {
      if ($playlists_model('get_by_id', $elem['playlist_id'])['status'] == 'public') {
        $playlists[] = $elem['playlist_id'];
      }
    }

    $this->db->where('task_id', $data['task_id']);
    $this->db->orderby('created', 'desc');
    $comment_items = $this->db->get('module_task_tracker_comments');
    $user_model    = $this->load->model('users');
    $comments      = [];
    foreach ($comment_items as $elem) {
      $user_item  = $user_model('get_by_id', $elem['user_id']);
      $comments[] = array(
        'id'      => $elem['id'],
        'task_id' => $elem['task_id'],
        'user'    => array(
          'id'           => $user_item['id'],
          'display_name' => $user_item['display_name']
        ),
        'comment' => $elem['comment'],
        'created' => $elem['created']
      );
    }

    $result = array(
      'task'      => $task[0],
      'users'     => $users,
      'media'     => $media,
      'playlists' => $playlists,
      'comments'  => $comments
    );

    $this->db->where('task_id', $data['task_id']);
    $payment = $this->db->get_one('module_task_tracker_payments');
    if ($payment) {
      $result['payment'] = array(
        'amount'  => $payment['amount'],
        'type'    => $payment['type'],
        'comment' => $payment['comment']
      );
    }

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

    $this->db->where('task_id', $data['task_id']);
    $result = $this->db->delete('module_task_tracker_comments');

    $this->db->where('task_id', $data['task_id']);
    $this->db->delete('module_task_tracker_payments');

    return [true, 'Successfully removed task from database.'];
  }

  public function updateTask ($data) {
    $task_id   = $data['id'];
    $task_data = array(
      'name'        => $data['name'],
      'description' => OBFHelpers::sanitize_html($data['description'])
    );

    $task_due = $data['due'];
    if ($task_due != '') {
      $task_data['due'] = strtotime($task_due . ' 12:00');
    } else {
      $task_data['due'] = null;
    }

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

    if (isset($data['payment_item'])) {
      $this->db->where('task_id', $task_id);
      $this->db->delete('module_task_tracker_payments');

      $comment = (isset($data['payment_comment']) ? $data['payment_comment'] : '');
      $payment = [
        'task_id'     => $task_id,
        'amount'      => $data['payment_amount'],
        'type'        => $data['payment_item'],
        'comment'     => $comment
      ];
      $this->db->insert('module_task_tracker_payments', $payment);
    }

    return [true, 'Successfully updated task'];
  }

  public function validateComment ($data) {
    $this->db->where('id', $data['task_id']);
    $task = $this->db->get('module_task_tracker');

    if (!$task) {
      return [false, 'Failed to retrieve task from database.'];
    }

    return [true, 'Comment data is valid.'];
  }

  public function addComment ($data) {
    $user_id = $this->user->param('id');
    $comment = [
      'task_id' => $data['task_id'],
      'user_id' => $user_id,
      'comment' => OBFHelpers::sanitize_html($data['comment']),
      'created' => time()
    ];
    $this->db->insert('module_task_tracker_comments', $comment);

    return [true, 'Comment submitted.'];
  }

  public function currentUserAssigned ($task_id) {
    $user_id = $this->user->param('id');

    $this->db->where('task_id', $task_id);
    $this->db->where('user_id', $user_id);
    $result = $this->db->get_one('module_task_tracker_users');

    return ($result ? true : false);
  }

  public function validateStatus ($data) {
    if ($data['status'] != 'new' && $data['status'] != 'in progress' && $data['status'] != 'complete') {
      return [false, 'Invalid task status selected.'];
    }

    return [true, 'Data is valid.'];
  }

  public function updateTaskStatus ($data) {
    $task_id     = $data['id'];
    $task_data   = array(
      'status' => $data['status']
    );

    $this->db->where('id', $task_id);
    if (!$this->db->update('module_task_tracker', $task_data)) {
      return [false, 'Failed to update task status in database.'];
    }

    return [true, 'Successfully updated task status.'];

  }
}
