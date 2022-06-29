
<form method="post" action="/file/upload/file" enctype="multipart/form-data">

	<div class="sharing">
		<h3>Sharing With:</h3>
		<?php foreach($sharing as $objectId => $label): ?>
			<div>
				<?php
					$checked = $label == "Me" ? "checked" : "";
					$noClick = $label == "Me" ? "style='pointer-events: none;'" : "";
				?>
				<input name="linkedEntityIds[]" type="checkbox" <?php print $checked ." ". $noClick; ?> value="<?php print $objectId; ?>" />
				<label><?php print $label; ?></label>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="form-item">
		<input type="file" id="Attachments__c[]" name="Attachments__c[]" />
	</div>

	<div class="form-item">
		<input type="submit" value="Upload File" />
	</div>

</form>