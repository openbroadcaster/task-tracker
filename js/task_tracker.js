OBModules.TaskTracker = new function () {
  
  /*-----------------
    OB INITIALIZATION
    -----------------*/
  
  this.init = function () {
    OB.Callbacks.add('ready', 0, OBModules.TaskTracker.initMenu);
  }
  
  this.initMenu = function () {
    OB.UI.addSubMenuItem('admin', 'Task Tracker', 'task_tracker', 
                         OBModules.TaskTracker.open, 151);
  }
  
  /*---------------------
    MAIN TASKTRACKER VIEW 
    ---------------------*/
  
  /* open() replaces the main UI with the TaskTracker module, and
  initializes various things. After showing the HTML, it adds droppable
  objects to the media and playlist input fields for adding new tasks, 
  and loads an overview of all existing tasks in the database. */
  this.open = function () {
    OB.UI.replaceMain('modules/task_tracker/task_tracker.html');
    
    if (OB.Settings.permissions.includes('task_tracker_module_manage')) {
      OBModules.TaskTracker.loadUsers();
    
      $('#task_tracker_due').datepicker({ dateFormat: "yy-mm-dd" });
      
      $('#task_tracker_media').droppable({
        drop: OBModules.TaskTracker.droppableMedia
      });
    
      $('#task_tracker_playlists').droppable({
        drop: OBModules.TaskTracker.droppablePlaylists
      });
    
      OBModules.TaskTracker.loadTaskOverview(true);
    } else {
      $('.task_manager').hide();
      
      OBModules.TaskTracker.loadTaskOverview(false);
      
    }
  }
  
  /* loadUsers() loads all users from the database into the HTML
  so they can be assigned to tasks. */
  this.loadUsers = function () {
    OB.API.post('tasktracker', 'loadUsers', {}, function (response) {
      var msg_result = (response.status ? 'success' : 'error');
      if (msg_result == 'error') {
        $('#task_tracker_message').obWidget(msg_result, response.msg);
      }
      
      $('#task_tracker_users').empty();
      
      if (response.status) {
        $(response.data).each(function (index, element) {
          var user_id   = element.id;
          var user_name = element.display_name;
          
          var item = $('<option/>');
          item.val(user_id);
          item.text(user_name);

          $('#task_tracker_users').append(item);
        });
      }
    });
  }
  
  /* droppableMedia(event, ui) and droppablePlaylists(event, ui) 
  define the droppable elements for adding and removing media and
  playlists to tasks. */
  this.droppableMedia = function (event, ui) {
    var private_media_alert = false;

    if ($(ui.draggable).attr('data-mode') == 'media') {
      $('.sidebar_search_media_selected').each(function (index, element) {

        if ($(element).attr('data-public_status') == 'private' ) {
          private_media_alert = true;
          return true;
        }

        var data_id = $(element).attr('data-id');
        var is_dup  = $('#task_tracker_media [data-id=' + data_id + ']');
        if (is_dup.length) {
          return true;
        }

        var $label  = $(element).attr('data-artist') + ' - ' + $(element).attr('data-title');
        var $result = $('<div/>').text($label).attr('data-id', $(element).attr('data-id')).prepend('<a onclick="return OBModules.TaskTracker.removeItem(this)" href="#">x</a> ');            
        $('#task_tracker_media').append($result);
      });     

      if (private_media_alert) {
        OB.UI.alert('Cannot use private items.');
      }

      OBModules.TaskTracker.updateMediaDummy();

      OB.Sidebar.mediaSelectNone();
    }
  }
  
  this.droppablePlaylists = function (event, ui) {
    var private_media_alert = false;
    if ($(ui.draggable).attr('data-mode') == 'playlist') {
      $('.sidebar_search_playlist_selected').each(function (index, element) {

        if ($(element).attr('data-status') == 'private') {
          private_media_alert = true;
          return true;
        }

        var data_id = $(element).attr('data-id');
        var is_dup  = $('#task_tracker_playlists [data-id=' + data_id + ']');
        if (is_dup.length) {
          return true;
        }

        var $label = $(element).attr('data-name') + ' - ' + $(element).attr('data-description');
        var $result = $('<div/>').text($label).attr('data-id', $(element).attr('data-id')).prepend('<a onclick="return OBModules.TaskTracker.removeItem(this)" href="#">x</a> ');
        $('#task_tracker_playlists').append($result);
      });

      if (private_media_alert) {
        OB.UI.alert('Cannot use private items.');
      }

      OBModules.TaskTracker.updatePlaylistsDummy();

      OB.Sidebar.playlistSelectNone();
    }
  }
  
  /* Utility methods to hide the dummy values when inserting media or
  playlists. */
  this.updateMediaDummy = function () {
    if ($('#task_tracker_media div').length > 1) {
      $('#task_media_dummy').hide();
    } else {
      $('#task_media_dummy').show();
    }
    
    return true;
  }
  
  this.updatePlaylistsDummy = function () {
    if ($('#task_tracker_playlists div').length > 1) {
      $('#task_playlists_dummy').hide();
    } else {
      $('#task_playlists_dummy').show();
    }
    
    return true;
  }
  
  /* removeItem(link) is called by links inside a list of new media or
  playlist items, and uses jQuery to remove the entire HTML element, 
  thereby both removing it from the view *and* stopping an item from being
  added to the database. */
  this.removeItem = function (link) {
    $(link).parent().remove();
    
    this.updateMediaDummy();
    this.updatePlaylistsDummy();
    
    return false;
  }
  
  /* addUser() adds the selected user to a list of users assigned to the
  current task. */
  this.addUser = function () {
    var item_name = $('#task_tracker_users option:selected').text();
    var item_val  = $('#task_tracker_users option:selected').val();
    
    var item = $('<div/>');
    item.attr('data-id', item_val);
    item.text(item_name);
    item.prepend('<a onclick="return OBModules.TaskTracker.removeItem(this)" href="#">x</a> ');
    
    if ($('#task_tracker_users_list [data-id=' + item_val + ']').length == 0) {
      $('#task_tracker_users_list').append(item);
    }
  }
  
  /* addTask() uses all the data in the input fields to add a new task to the 
  database. */
  this.addTask = function () {
    $('#task_tracker_message').obWidget('info', 'Adding new task to database.');
    
    var post              = {};
    post.task_name        = $('#task_tracker_name').val();
    post.task_description = $('#task_tracker_description').val();
    post.task_due         = $('#task_tracker_due').val();
    
    post.task_users       = []; 
    $('#task_tracker_users_list div').each(function (index, element) {
      post.task_users.push($(element).attr('data-id'));
    });
    
    post.task_media       = [];
    $('#task_tracker_media div').each(function (index, element) {
      post.task_media.push($(element).attr('data-id'));
    });
    post.task_playlists   = [];
    $('#task_tracker_playlists div').each(function (index, element) {
      post.task_playlists.push($(element).attr('data-id'));
    });
    
    OB.API.post('tasktracker', 'addTask', post, function (response) {
      if (response.status) {
        /* $('#task_tracker_name').val('');
        $('#task_tracker_description').val(''); */
        OBModules.TaskTracker.open();
      }
      
      var msg_result = (response.status ? 'success' : 'error');
      $('#task_tracker_message').obWidget(msg_result, response.msg);
    });
  }
  
  /* loadTaskOverview(editable) is part of the open() method for the task
  list,  posting to the controller and putting all the retrieved information
  in the right fields in the HTML. */
  this.loadTaskOverview = function (editable, sort_by = 'created', sort_dir = 'desc') {
    var post = {}
    post.sort_by  = sort_by;
    post.sort_dir = sort_dir;
    
    OB.API.post('tasktracker', 'loadTaskOverview', post, function (response) {
      
      var msg_result = (response.status ? 'success' : 'error');
      if (msg_result == 'error') {
        $('#task_tracker_message').obWidget(msg_result, response.msg);
      }
      
      $('#task_tracker_list').empty();
      
      if (response.status) {
        if (response.data.length > 0) {
          var item = $('<tr/>');
          var sort_callback = 'return OBModules.TaskTracker.sortOverview(this, \'asc\')';
          var sort_current  = 'return OBModules.TaskTracker.sortOverview(this, \'desc\')';
          var is_current    = (sort_dir == 'asc') ? sort_by : null;
          
          item.append($('<th/>').append($('<a/>').text('Name').attr('onclick', (is_current == 'name') ? sort_current : sort_callback)));
          item.append($('<th/>').append($('<a/>').text('Description').attr('onclick', (is_current == 'description') ? sort_current : sort_callback)));
          item.append($('<th/>').append($('<a/>').text('Created').attr('onclick', (is_current == 'created') ? sort_current : sort_callback)));
          item.append($('<th/>').append($('<a/>').text('Due').attr('onclick', (is_current == 'due') ? sort_current : sort_callback)));
          item.append($('<th/>').append($('<a/>').text('Status').attr('onclick', (is_current == 'status') ? sort_current : sort_callback)));
          item.append($('<th/>'));
          item.append($('<th/>'));

          /* item.append($('<th/>').text('View'));
          if (editable) item.append($('<th/>').text('Delete')); */
          
          $('#task_tracker_list').append(item);
        }
        
        $(response.data).each(function (index, element) {
          var item = $('<tr/>');
          var task_created = format_timestamp(element.created).slice(0, 10);
          var task_due     = format_timestamp(element.due).slice(0, 10);
          
          var task_status_class = 'task_item_new';
          switch (element.status) {
            case 'new':
              task_status_class = 'task_item_new';
              break;
            case 'in progress':
              task_status_class = 'task_item_in_progress';
              break;
            case 'complete':
              task_status_class = 'task_item_complete';
              break;
          }
          
          item.attr('data-task_id', element.id);
          
          item.append($('<td/>').text(element.name));
          item.append($('<td/>').text(element.description));
          item.append($('<td/>').text(task_created).addClass('task_table_date'));
          item.append($('<td/>').text(task_due).addClass('task_table_date'));
          item.append($('<td/>').text(element.status).addClass(task_status_class));
          
          var $buttons = $('<td />');
          $buttons.append('<a class="button" onclick="return OBModules.TaskTracker.viewTask(this)" href="#">View</a>');
          if (editable) $buttons.append('<a class="button delete" onclick="return OBModules.TaskTracker.removeTask(this)" href="#">Delete</a>');
          item.append($buttons);
          
          $('#task_tracker_list').append(item);
        })
      }
      
      if ($('#task_tracker_list tr').length > 0) {
        $('#task_list_dummy').hide();
      } else {
        $('#task_list_dummy').show();
      }
      
    });
  }
  
  /* sortOverview takes a </th> link and checks its value to see what to sort 
  by, it also takes a sort direction set in the link (dynamically adjusted 
  based on whether it was already clicked for ascending sort before). It then
  calls reloads the task overview, this time sorting by a specific value and
  in a specific direction. */
  this.sortOverview = function (link, sort_dir) {
    var sort_by = $(link).text().toLowerCase();
    
    if (OB.Settings.permissions.includes('task_tracker_module_manage')) {
      OBModules.TaskTracker.loadTaskOverview(true, sort_by, sort_dir);
    } else {
      OBModules.TaskTracker.loadTaskOverview(false, sort_by, sort_dir);
    }
    
    return false;
  }
  
  /* removeTask(link) removes an individual task from the database by
  calling an auxiliary method (removeTaskConfirm(task_id)), and then
  reloads the task overview inside the main view. */
  this.removeTask = function (link) {
    var task_id = $(link).parents('tr').first().attr('data-task_id');
    
    OB.UI.confirm({
      text: "Are you sure you want to delete this task?", 
      okay_class: "delete",
      callback: function () {
        OBModules.TaskTracker.removeTaskConfirm(task_id);
      }
    });
    
    return false;
  }
  
  this.removeTaskConfirm = function (task_id) {
    var post      = {};
    post.task_id  = task_id;
    
    OB.API.post('tasktracker', 'removeTask', post, function (response) {
      
      var msg_result = (response.status ? 'success' : 'error');
      $('#task_tracker_message').obWidget(msg_result, response.msg);
      
      OBModules.TaskTracker.loadTaskOverview(true);
    });
    
    return false;
  }
  
  /*-------------------- 
    INDIVIDUAL TASK VIEW
    --------------------*/
  
  /* viewTask(link) replaces the TaskTracker view with a new one for 
  viewing individual tasks, and is called from inside the main view
  whenever someone tries to view or edit a task. Note that the link
  in viewTask(link) relies on either getting a proper element from 
  the main view, or having a task ID passed to it. */
  this.viewTask = function (link) {
    OB.UI.replaceMain('modules/task_tracker/task_tracker_view.html');
    
    var post = {};
    if (typeof(link) == "object") {   
      post.task_id = $(link).parents('tr').first().attr('data-task_id');
    } else {
      post.task_id = link;
    }
    OB.API.post('tasktracker', 'viewTask', post, function (response) {
      
      var msg_result = (response.status ? 'success' : 'error');
      if (msg_result == 'error') {
        OBModules.TaskTracker.open();
        
        $('#task_tracker_message').obWidget(msg_result, response.msg);
      }
      
      if (response.status) {
        var task_id          = response.data.task.id;
        var task_created     = response.data.task.created;
        var task_name        = response.data.task.name;
        var task_description = response.data.task.description;
        var task_status      = response.data.task.status;
        var task_due         = response.data.task.due;
        var task_users       = response.data.users;
        var task_media       = response.data.media;
        var task_playlists   = response.data.playlists;
        var task_comments    = response.data.comments;        
        
        var task_perms       = response.data.permissions;
        
        $('#task_tracker_current_id').val(task_id);
        $('#task_tracker_created').text(format_timestamp(task_created));
        
        $('#task_tracker_name').val(task_name);
        $('#task_tracker_name_view').text(task_name);
        
        $('#task_tracker_description').val(task_description);
        $('#task_tracker_description_view').text(task_description);
        
        $('#task_tracker_status option[value="' + task_status + '"]').prop('selected', true);
        $('#task_tracker_status_view').text(task_status);
        
        $('#task_tracker_due').datepicker({ dateFormat: "yy-mm-dd" });
        $('#task_tracker_due').val(format_timestamp(task_due).slice(0, 10));
        $('#task_tracker_due_view').text(format_timestamp(task_due).slice(0, 10));
        
        if (task_perms == 'edit') {
          OBModules.TaskTracker.loadUsers();
          
          $('#task_tracker_module .edit-perms').show();
          $('#task_tracker_module .view-perms').hide();
        } else {          
          $('#task_tracker_module .edit-perms').hide();
          $('#task_tracker_module .view-perms').show();
        }
                
        $(task_users).each(function (index, element) {
          var elem_user_id      = element.user.id;
          var elem_user_display = element.user.display_name;
          
          $html = $('<div/>').text(elem_user_display);
          $html.attr('data-id', elem_user_id);
          
          if (task_perms == 'edit') {
            $html.prepend('<a onclick="return OBModules.TaskTracker.removeItem(this)" href="#">x</a> ');
          }
          
          $('#task_tracker_users_list').append($html);
        });
        
        $(task_media).each(function (index, element) {
          var elem_media_id     = element.media.id;
          var elem_media_artist = element.media.artist;
          var elem_media_title  = element.media.title;
          
          $html = $('<div/>').append( $('<span />').text(elem_media_artist + ' - ' + elem_media_title).click(function() { OB.Media.detailsPage(elem_media_id); }) );
          $html.attr('data-id', elem_media_id);
          
          if (task_perms == 'edit') {
            $html.prepend('<a onclick="return OBModules.TaskTracker.removeItem(this)" href="#">x</a> ');
          }
          
          $('#task_tracker_media').append($html);
        });
        
        $(task_playlists).each(function (index, element) {
          var elem_playlist_id   = element.playlist.id;
          var elem_playlist_name = element.playlist.name;
          var elem_playlist_desc = element.playlist.description;
          
          $html = $('<div/>').append( $('<span />').text(elem_playlist_name + ' - ' + elem_playlist_desc).click(function() { OB.Playlist.detailsPage(elem_playlist_id); }) );
          $html.attr('data-id', elem_playlist_id);
          
          if (task_perms == 'edit') {
            $html.prepend('<a onclick="return OBModules.TaskTracker.removeItem(this)" href="#">x</a> ');
          }
          
          $('#task_tracker_playlists').append($html);
        });
        
        if (task_perms == 'edit') {
          $('#task_tracker_media').droppable({
            drop: OBModules.TaskTracker.droppableMedia
          });
    
          $('#task_tracker_playlists').droppable({
            drop: OBModules.TaskTracker.droppablePlaylists
          });
        }

        $(task_comments).each(function (index, element) {
          var elem_comm_text = element.comment;
          var elem_comm_user = element.user.display_name;
          var elem_comm_time = format_timestamp(element.created);
          
          $html = $('<div/>');
          $html_text = $('<p/>').text(elem_comm_text);
          $html_user = $('<h4/>').text(elem_comm_user);
          $html_time = $('<p/>').append($('<em/>').text(elem_comm_time));
          $html.append($html_user);
          $html.append($html_text);
          $html.append($html_time);
          
          $('#task_tracker_comment_list').append($html);
        });
        
        OBModules.TaskTracker.updateTaskDummy(task_perms);
      }
      
    });
    
    return false;
  }  
  
  /* refreshComments(task_id) is called when viewTask is excessive, and
  when only the list of comments need to be reloaded: this is helpful
  so the screen doesn't flash unnecessarily after posting a comment,
  for example. */
  this.refreshComments = function (task_id) {
    var post = {};
    post.task_id = task_id;
    
    OB.API.post('tasktracker', 'viewTask', post, function (response) {
      
      var msg_result = (response.status ? 'success' : 'error');
      if (msg_result == 'error') {
        $('#task_tracker_message').obWidget(msg_result, response.msg);
      }
      
      if (response.status) {
        $('#task_tracker_comment_list').empty();
        
        var task_comments    = response.data.comments; 
        $(task_comments).each(function (index, element) {
          var elem_comm_text = element.comment;
          var elem_comm_user = element.user.display_name;
          var elem_comm_time = format_timestamp(element.created);
          
          $html = $('<div/>');
          $html_text = $('<p/>').text(elem_comm_text);
          $html_user = $('<h4/>').text(elem_comm_user);
          $html_time = $('<p/>').append($('<em/>').text(elem_comm_time));
          $html.append($html_user);
          $html.append($html_text);
          $html.append($html_time);
          
          $('#task_tracker_comment_list').append($html);
        });
      }
    });
  }
  
  /* updateTaskDummy(perms) is a helper methods that updates the dummy
  value for playlists and media based on 1. the view/edit permissions
  for that task, and 2. whether or not there are any items in the media
  and playlist lists. */
  this.updateTaskDummy = function (perms) {
    
    if (perms == 'edit') {
      $('#task_media_dummy').text('(drag media here)');
      $('#task_playlists_dummy').text('(drag playlists here)');
      $('#task_tracker_users_list_dummy').text();
    } else {
      $('#task_media_dummy').text('(no media in task)');
      $('#task_playlists_dummy').text('(no playlists in task)');
      $('#task_tracker_users_list_dummy').text('(no users assigned to task)');
    }
    
    if ($('#task_tracker_media div').length > 1) {
      $('#task_media_dummy').hide();
    } else {
      $('#task_media_dummy').show();
    }
    
    if ($('#task_tracker_playlists div').length > 1) {
      $('#task_playlists_dummy').hide();
    } else {
      $('#task_playlists_dummy').show();
    }
    
    if ($('#task_tracker_users_list div').length > 1) {
      $('#task_tracker_users_list_dummy').hide();
    } else {
      $('#task_tracker_users_list_dummy').show();
    }
  }
  
  /* updateTask() updates the values of a single task viewed in the 
  individual task view. */
  this.updateTask = function () {
    var post = {};
    post.task_id          = $('#task_tracker_current_id').val();
    post.task_name        = $('#task_tracker_name').val();
    post.task_description = $('#task_tracker_description').val();
    post.task_status      = $('#task_tracker_status').val();
    post.task_due         = $('#task_tracker_due').val();
    
    post.task_users       = [];
    $('#task_tracker_users_list div').each(function (index, element) {
      post.task_users.push($(element).attr('data-id'));
    });
    
    post.task_media       = [];
    $('#task_tracker_media div').each(function (index, element) {
      post.task_media.push($(element).attr('data-id'));
    });
    post.task_playlists   = [];
    $('#task_tracker_playlists div').each(function (index, element) {
      post.task_playlists.push($(element).attr('data-id'));
    });
        
    OB.API.post('tasktracker', 'updateTask', post, function (response) {
      OBModules.TaskTracker.open();
      
      var msg_result = (response.status ? 'success' : 'error');
      $('#task_tracker_message').obWidget(msg_result, response.msg);
    });
        
    return false;
  }
  
  /* closeTask() closes the individual task view and returns to the 
  main TaskTracker view (by calling open()). */
  this.closeTask = function () {
    OBModules.TaskTracker.open();
    
    return false;
  }
  
  /* addComment() adds a comment to the currently opened task, then
  refreshes the task view. */
  this.addComment = function () {
    var post = {};
    var task_id = $('#task_tracker_current_id').val();
    post.task_id = task_id; 
    post.comment = $('#task_tracker_comment').val();
    
    OB.API.post('tasktracker', 'addComment', post, function (response) {
      OBModules.TaskTracker.refreshComments(task_id);
      
      var msg_result = (response.status ? 'success' : 'error');
      $('#task_tracker_message').obWidget(msg_result, response.msg);
    })
    
    return false;
  }
}