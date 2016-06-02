<?php echo '<?xml version="1.0" encoding="utf-8" ?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">	
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<meta http-equiv="content-language" content="en" />
		<meta name="description" content="" /> 
		<meta name="keywords" content="" /> 
		<title>Random text generator</title>
		<link href="style.css" rel="stylesheet" type="text/css" media="screen" />
	</head>
	<body>
		<div id="holder">
			<h1>Random Text Generator</h1>
			<hr />
			<div id="configuration">
				<form action="reader_index.php" method="post">
						<h2>Configuration</h2>
						<p><label>Table:<br /><select name="table">
						<?php foreach($tables as $value => $title):
								$selected = '';
								if($value == $table) $selected = ' selected="selected"';
						?>
								<option value="<?php echo $value; ?>"<?php echo $selected; ?>><?php echo $title;?></option>
						<?php endforeach; ?>
						</select></label></p>
						<p><label>Number&nbsp;of&nbsp;paragraphs:<br /><input type="text" id="paragraphs" name="paragraphs" maxlength="3" value="<?php echo $paragraphs; ?>" /></label></p>
						<p><label>Paragraph&nbsp;length:<br /><input type="text" id="length" name="length" maxlength="4" value="<?php echo $length; ?>" /></label></p>
						<p>
							<label><input type="checkbox" name="html-mode" <?php if($htmlMode) echo ' checked="checked"'; ?> /> HTML mode</label><br/>
							<label><input type="checkbox" name="suppress-quotes" <?php if($suppressQuotes) echo ' checked="checked"';?> /> Suppress quotes</label><br/>
							<label><input type="checkbox" name="suppress-marks" <?php if($suppressMarks) echo ' checked="checked"';?> /> Suppress marks</label><br/>
							<label><input type="checkbox" name="suppress-digits" <?php if($suppressDigits) echo ' checked="checked"';?> /> Suppress digits</label><br/>
							<label><input type="checkbox" name="ucase-first" <?php if($ucaseFirst) echo ' checked="checked"';?> /> Uppercase first characters</label><br/>
						</p>
					<p class="button" ><input type="submit" id="submit" value="Generate!" /></p>
				</form>
			</div>
			<h2>Generated text</h2>
			<div id="text" <?php if($htmlMode) echo ' class="html"'; ?>>
				<?php 
				foreach($texts as $text) {
					echo "<p>$text</p>";
				}
				?>
			</div>
		</div>
	</body>
</html>