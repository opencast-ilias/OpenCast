/**
 * AJAX Request, Get Channel Infos
 *
 * @author Oskar Truffer <ot@studer-raimann.ch>
 * @author Fabian Schmid <fs@studer-raimann.ch>
 *
 */
$(document).ready(function () {

	xoctWaiter.init();

	$('#existing_identifier').change(function () {

		var identifier = $('#existing_identifier').val();
		xoctWaiter.show();
		$.ajax({
			url: "./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Series/json.php",
			type: "GET",
			data: {
				"identifier": identifier
			}
		}).done(function (data, textStatus, jqXHR) {
			console.log("HTTP Request Succeeded: " + jqXHR.status);
			console.log(data);

			$('#title').val(data.title);
			$('#description').val(data.identifier);
			$('#introduction_text').val('');
			$('#license').val(data.license);
			$('#department').val(data.department);

		}).fail(function (jqXHR, textStatus, errorThrown) {
			console.log("HTTP Request Failed");
		}).always(function () {
			/* ... */
			xoctWaiter.hide();
		});


	});
});
