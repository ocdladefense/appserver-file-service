
<form method="post" action="/file/upload/file" enctype="multipart/form-data">

	<div class="form-item">
		<input type="file" id="Attachments__c[]" name="Attachments__c[]"  required />
	</div>

	<div class="form-item">
		<input type="submit" value="Upload File" />
	</div>

	<br />
	<br />

	
	<div class="sharing">
		<h3>Sharing With:</h3>
		<?php foreach($sharing as $objectId => $label): ?>
			<div>
				<input name="linkedEntityIds[]" type="checkbox" value="<?php print $objectId; ?>" />
				<label><?php print $label; ?></label>
			</div>
		<?php endforeach; ?>

		<input type="hidden" name="linkedEntityIds[]" value="<?php print $contactId; ?>">
	</div>

</form>