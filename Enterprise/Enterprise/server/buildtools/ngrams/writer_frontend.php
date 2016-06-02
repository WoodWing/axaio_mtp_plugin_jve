<?php echo '<?xml version="1.0" encoding="utf-8" ?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">	
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<meta http-equiv="content-language" content="en" />
		<meta name="description" content="" /> 
		<meta name="keywords" content="" /> 
		<title>Import for Random Text Generator</title>
		<link href="style.css" rel="stylesheet" type="text/css" media="screen" />
	</head>
	<body>
		<h1>Import for Random Text Generator</h1><hr />
		<p><font color="red"><?php echo $error; ?></font></p>
		<p><font color="blue"><?php echo $message; ?></font></p>
		<form action="writer_index.php" method="post">
			<div id="configuration">
				<p><label>Book title:<br /><input type="text" id="bookTitle" name="bookTitle" maxlength="100" value="<?php echo $bookTitle; ?>" /></label></p>

				<p><label>Gram:<br /><select name="nGrams">
					<?php foreach(array('bi-gram' => '2', 'tri-gram' => '3', 'quad-gram' => '4') as $key => $value):
						$selected = '';
						if($value == $nGrams) $selected = ' selected="selected"';
					?>
					<option value="<?php echo $value; ?>"<?php echo $selected; ?>><?php echo $key;?></option>
					<?php endforeach; ?>
				</select></label></p>
	
				<p><label>Language:<br /><select name="langCode">
					<?php foreach(array('English' => 'en', 'Dutch' => 'nl') as $key => $value):
						$selected = '';
						if($value == $langCode) $selected = ' selected="selected"';
					?>
					<option value="<?php echo $value; ?>"<?php echo $selected; ?>><?php echo $key;?></option>
					<?php endforeach; ?>
				</select></label></p>

				<p><label>Type:<br /><select name="type">
					<?php foreach(array('Word based' => 'word', 'Character based' => 'char') as $key => $value):
						$selected = '';
						if($value == $type) $selected = ' selected="selected"';
					?>
					<option value="<?php echo $value; ?>"<?php echo $selected; ?>><?php echo $key;?></option>
					<?php endforeach; ?>
				</select></label></p>
	
				<p class="button" ><input type="submit" id="submit" value="Write Book" /></p>
			</div>
			
			<p><label>Raw text:  <br /><textarea id="rawText" name="rawText" cols="60" rows="40"><?php echo $rawText; ?></textarea></label></p>
		</form>
	</body>
</html>