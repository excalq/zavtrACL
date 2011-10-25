
// On page load, if there was a preselected 'action', repopulate the Ajax menu with that value
$(document).ready(function() {
	if (selectedAction) {
	  fetchAjaxActions(); // Do automatically on page load
	}
});

// When the controller select menu changes, update the 'actions' select menu
$(document).ready(function(){
  $('#controller').live('change', function() { fetchAjaxActions(); }); // Do on change event of the controllers select menu
});

// Use the value of the "controllers" select menu to fetch the data for "actions"
function fetchAjaxActions() {
  console.log()
  if($('#controller').val().length != 0) {
	$.getJSON('/auth_acl/ajax_acl_admin_get_actions/' + $('#controller').val(),
				function(actions) {
				  if(actions !== null) {
					populateACLActions(actions);
				  }
				}
			  );
	}
}

// Populate actions select list with: <option value="action">action</option>
function populateACLActions(actions) {
  var options = '<option value="">Select Action</option>';
  options += '<option value="*">Any Action (*)</option>';

  $.each(actions, function(index, action_opt) {
	
	// If PHP has pre-populated this server into the SelectedServers Array, pre-select it
	if (selectedAction == action_opt) {
		preSelected = ' selected="selected"'
	} else { // If selectedAction did not match anything, leave unselected
		preSelected = '';
	}
	
	options += '<option value="' + action_opt + '"' + preSelected + '>' + action_opt + '</option>';
  });
  $('#action').html(options);
  $('#AuthACLActionDiv').show();

}