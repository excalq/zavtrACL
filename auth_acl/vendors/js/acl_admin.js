$(document).ready(function(){
  $('#controller').live('change', function() {
	if($(this).val().length != 0) {
	  $.getJSON('/auth_acl/ajax_acl_admin_get_actions/' + $(this).val(),
				  function(actions) {
					if(actions !== null) {
					  populateACLActions(actions);
					}
		});
	  }
	});
});

// Populate actions select list with: <option value="action">action</option>
function populateACLActions(actions) {
  var options = '<option value="">Select Action</option>';
  options += '<option value="*">Any Action (*)</option>';

  $.each(actions, function(index, actions) {
	options += '<option value="' + actions + '">' + actions + '</option>';
  });
  $('#action').html(options);
  $('#AuthACLActionDiv').show();

}